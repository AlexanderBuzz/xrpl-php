<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Client\Submitter;
use Hardcastle\XRPL_PHP\Wallet\Wallet;

/**
 * Reliable submission against the Testnet.
 *
 * submitAndWait() polls the ledger with a three second sleep between attempts,
 * so it cannot be covered by the unit suite. Everything below needs a funded
 * account and a closing ledger, which makes it slow and dependent on the
 * faucet - hence the separate group:
 *
 *     vendor/bin/phpunit --group integration-slow
 *     vendor/bin/phpunit --exclude-group integration-slow   (the default in CI)
 *
 * It exercises the paths that unit tests reach only in pieces: autofill against
 * a live server, signing, submission, and the polling loop that decides when an
 * outcome is final.
 */
#[Group('integration-slow')]
final class SubmitAndWaitTest extends TestCase
{
    private const TESTNET_URL = "https://s.altnet.rippletest.net:51234";

    /** @psalm-suppress PropertyNotSetInConstructor */
    private JsonRpcClient $client;

    public function setUp(): void
    {
        $this->client = new JsonRpcClient(self::TESTNET_URL);
    }

    /**
     * The whole path in one go: an unsigned transaction plus a wallet, filled
     * in, signed, submitted, and polled until it is in a validated ledger.
     *
     * Passing an unsigned transaction together with a wallet is the case that
     * used to fail with "Transaction must be signed", because getSignedTx()
     * returned the tx_blob envelope of Wallet::sign() rather than an array.
     */
    public function testSubmitAndWaitReachesAValidatedLedger(): void
    {
        $sender = $this->client->fundWallet();
        $receiver = $this->client->fundWallet();

        $response = $this->client->submitAndWait(
            [
                'TransactionType' => 'Payment',
                'Account' => $sender->getAddress(),
                'Destination' => $receiver->getAddress(),
                'Amount' => '1000000',
            ],
            autofill: true,
            failHard: false,
            wallet: $sender
        );

        $result = $response->getResult();

        $this->assertTrue($result['validated'], 'the transaction has to be in a validated ledger');
        $this->assertEquals('tesSUCCESS', $result['meta']['TransactionResult']);
        $this->assertEquals('Payment', $result['tx_json']['TransactionType'] ?? $result['TransactionType']);
    }

    /**
     * The same through the object form, and with the transaction signed by the
     * caller beforehand - the way every example does it.
     */
    public function testSubmitterAcceptsAPreSignedBlob(): void
    {
        $wallet = $this->client->fundWallet();
        $receiver = $this->client->fundWallet();

        $signed = $wallet->sign($this->client->autofill([
            'TransactionType' => 'Payment',
            'Account' => $wallet->getAddress(),
            'Destination' => $receiver->getAddress(),
            'Amount' => '1000000',
        ]));

        $response = (new Submitter($this->client))->submitAndWait($signed['tx_blob']);
        $result = $response->getResult();

        $this->assertTrue($result['validated']);
        $this->assertEquals('tesSUCCESS', $result['meta']['TransactionResult']);
        $this->assertEquals($signed['hash'], $result['hash']);
    }

    /**
     * Without a LastLedgerSequence there is no point at which polling could
     * stop, so submission is refused before it starts.
     */
    public function testSubmitAndWaitRefusesATransactionWithoutLastLedgerSequence(): void
    {
        $wallet = $this->client->fundWallet();

        $signed = $wallet->sign([
            'TransactionType' => 'AccountSet',
            'Account' => $wallet->getAddress(),
            'Fee' => '12',
            'Sequence' => 1,
        ]);

        $this->expectExceptionMessage('LastLedgerSequence');

        (new Submitter($this->client))->submitAndWait($signed['tx_blob']);
    }
}
