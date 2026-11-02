<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Models\Account\AccountInfoRequest;
use Hardcastle\XRPL_PHP\Models\Account\AccountObjectsRequest;
use Hardcastle\XRPL_PHP\Models\BaseRequest;
use Hardcastle\XRPL_PHP\Models\BaseResponse;
use Hardcastle\XRPL_PHP\Utils\Utilities;
use Hardcastle\XRPL_PHP\Wallet\Wallet;
use function Hardcastle\XRPL_PHP\Sugar\xrpToDrops;

/**
 * Permission Delegation (XLS-75, PermissionDelegationV1_1) against the
 * Testnet, with the roles of XLS-75 §5.1: Isaac delegates, Alice is his
 * delegate, Bob receives.
 *
 * A delegated transaction carries Account = the delegating account and
 * Delegate = the delegate, is signed by the delegate with sign() like any
 * transaction, uses the delegating account's Sequence and charges the fee
 * to the delegate.
 */
#[Group('integration-slow')]
final class DelegationTest extends TestCase
{
    private const TESTNET_URL = 'https://s.altnet.rippletest.net:51234';

    /** @psalm-suppress PropertyNotSetInConstructor */
    private JsonRpcClient $client;

    public function setUp(): void
    {
        $this->client = new JsonRpcClient(self::TESTNET_URL);
    }

    public function testADelegateSendsForTheDelegatingAccount(): void
    {
        $isaac = $this->client->fundWallet();
        $alice = $this->client->fundWallet();
        $bob = $this->client->fundWallet();

        // Isaac lets Alice send payments and set his domain, nothing else
        $this->submitAndAssert($isaac, [
            'TransactionType' => 'DelegateSet',
            'Account' => $isaac->getAddress(),
            'Authorize' => $alice->getAddress(),
            'Permissions' => [
                ['Permission' => ['PermissionValue' => 'Payment']],               // a transaction type
                ['Permission' => ['PermissionValue' => 'AccountDomainSet']],      // a granular permission
            ],
        ], 'DelegateSet');

        $objects = $this->rpcResult(new AccountObjectsRequest(account: $isaac->getAddress(), type: 'delegate', ledgerIndex: 'validated'));
        $this->assertCount(1, $objects['account_objects']);
        $this->assertSame($alice->getAddress(), $objects['account_objects'][0]['Authorize']);

        // A Payment from Isaac to Bob, signed by Alice: Isaac's Sequence, Alice's fee
        $isaacBefore = $this->accountData($isaac);
        $aliceBefore = $this->accountData($alice);

        $payment = $this->client->autofill([
            'TransactionType' => 'Payment',
            'Account' => $isaac->getAddress(),
            'Delegate' => $alice->getAddress(),
            'Destination' => $bob->getAddress(),
            'Amount' => xrpToDrops('1'),
        ]);
        $this->assertSame($isaacBefore['Sequence'], $payment['Sequence'], 'the delegating account\'s sequence');

        $result = $this->submitAndAssert($alice, $payment, 'delegated Payment');
        $this->assertSame($alice->getAddress(), $result['tx_json']['Delegate'] ?? $result['Delegate'] ?? null);

        $isaacAfter = $this->accountData($isaac);
        $aliceAfter = $this->accountData($alice);
        $this->assertSame(
            (int) xrpToDrops('1'),
            (int) $isaacBefore['Balance'] - (int) $isaacAfter['Balance'],
            'Isaac pays the amount and no fee'
        );
        $this->assertSame(
            (int) $payment['Fee'],
            (int) $aliceBefore['Balance'] - (int) $aliceAfter['Balance'],
            'Alice, the delegate, pays the fee'
        );
        $this->assertSame($isaacBefore['Sequence'] + 1, $isaacAfter['Sequence']);
        $this->assertSame($aliceBefore['Sequence'], $aliceAfter['Sequence'], 'the delegate\'s sequence does not move');

        // The granular permission: Alice may set Isaac's Domain through an AccountSet with Domain only
        $domain = Utilities::convertStringToHex('example.com');
        $this->submitAndAssert($alice, $this->client->autofill([
            'TransactionType' => 'AccountSet',
            'Account' => $isaac->getAddress(),
            'Delegate' => $alice->getAddress(),
            'Domain' => $domain,
        ]), 'delegated AccountSet (AccountDomainSet)');
        $this->assertSame($domain, $this->accountData($isaac)['Domain'] ?? null);

        // Not granted: a TrustSet on Isaac's behalf is refused before it reaches a ledger
        $trustSet = $this->client->autofill([
            'TransactionType' => 'TrustSet',
            'Account' => $isaac->getAddress(),
            'Delegate' => $alice->getAddress(),
            'LimitAmount' => ['currency' => 'USD', 'issuer' => $bob->getAddress(), 'value' => '100'],
        ]);
        $this->assertEngineResult('terNO_DELEGATE_PERMISSION', $alice, $trustSet, 'a TrustSet Alice was not granted');

        // An empty Permissions list deletes the Delegate object, and with it every permission
        $this->submitAndAssert($isaac, [
            'TransactionType' => 'DelegateSet',
            'Account' => $isaac->getAddress(),
            'Authorize' => $alice->getAddress(),
            'Permissions' => [],
        ], 'DelegateSet revoking');

        $objects = $this->rpcResult(new AccountObjectsRequest(account: $isaac->getAddress(), type: 'delegate', ledgerIndex: 'validated'));
        $this->assertCount(0, $objects['account_objects']);

        $payment = $this->client->autofill([
            'TransactionType' => 'Payment',
            'Account' => $isaac->getAddress(),
            'Delegate' => $alice->getAddress(),
            'Destination' => $bob->getAddress(),
            'Amount' => xrpToDrops('1'),
        ]);
        $this->assertEngineResult('terNO_DELEGATE_PERMISSION', $alice, $payment, 'a Payment after revoking');
    }

    /**
     * Autofill when needed, sign, submit, wait; assert tesSUCCESS and print the
     * hash, so a run leaves its Testnet evidence in the log.
     */
    private function submitAndAssert(Wallet $signer, array $tx, string $label): array
    {
        if (!isset($tx['Sequence'])) {
            $tx = $this->client->autofill($tx);
        }
        $signed = $signer->sign($tx);
        $result = $this->client->submitAndWait($signed['tx_blob'])->getResult();

        $this->assertSame('tesSUCCESS', $result['meta']['TransactionResult'], $label);
        fwrite(STDERR, sprintf("\n%s: %s\n", $label, $signed['hash']));

        return $result;
    }

    private function assertEngineResult(string $expected, Wallet $signer, array $tx, string $label): void
    {
        $result = $this->client->submit($signer->sign($tx)['tx_blob'])->getResult();
        $this->assertSame($expected, $result['engine_result'] ?? null, $label);
    }

    private function accountData(Wallet $wallet): array
    {
        return $this->rpcResult(new AccountInfoRequest(account: $wallet->getAddress(), ledgerIndex: 'validated'))['account_data'];
    }

    private function rpcResult(BaseRequest $request): array
    {
        $response = $this->client->syncRequest($request);
        $this->assertInstanceOf(BaseResponse::class, $response);

        return $response->getResult();
    }
}
