<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Models\Transaction;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use Hardcastle\XRPL_PHP\Wallet\Wallet;

/**
 * Every transaction model has to survive encode -> decode unchanged.
 *
 * TransactionTypesTest only checks the shape of the model. This test puts the
 * models through the binary codec, which is where a number of transaction
 * types used to break: the AMM classes were named Amm* and therefore
 * serialized as TransactionType 0 (Payment), the XChain types could not be
 * serialized at all, and NFTokenCreateOffer carried a misspelled NFTokenId.
 */
final class TransactionRoundtripTest extends TestCase
{
    private const SEED = 'sEd7rnfWxwJmRditu2UpSsrZDRgtctn';

    private const ACCOUNT = 'rPT1Sjq2YGrBMTttX4GZHjKu9dyfzbpAYe';

    private const OTHER_ACCOUNT = 'rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh';

    private const MPT_ISSUANCE_ID = '000004C463C52827307480341125DA0577DEFC38405B0E3E';

    private const HASH = 'ABABABABABABABABABABABABABABABABABABABABABABABABABABABABABABABAB';

    /** @psalm-suppress PropertyNotSetInConstructor */
    private BinaryCodec $binaryCodec;

    protected function setUp(): void
    {
        $this->binaryCodec = new BinaryCodec();
    }

    public static function transactionProvider(): array
    {
        $usd = ['currency' => 'USD', 'issuer' => self::OTHER_ACCOUNT];
        $usdAmount = $usd + ['value' => '10'];

        $cases = [
            // MPTokensV1
            'MPTokenIssuanceCreate' => [
                'AssetScale' => 2,
                'TransferFee' => 314,
                'MaximumAmount' => '100000000',
                'MPTokenMetadata' => '4D65746164617461',
            ],
            'MPTokenIssuanceDestroy' => ['MPTokenIssuanceID' => self::MPT_ISSUANCE_ID],
            'MPTokenIssuanceSet' => [
                'MPTokenIssuanceID' => self::MPT_ISSUANCE_ID,
                'Holder' => self::OTHER_ACCOUNT,
            ],
            'MPTokenAuthorize' => ['MPTokenIssuanceID' => self::MPT_ISSUANCE_ID],

            // Credentials
            'CredentialCreate' => [
                'Subject' => self::OTHER_ACCOUNT,
                'CredentialType' => '4B5943',
                'Expiration' => 789,
            ],
            'CredentialAccept' => [
                'Issuer' => self::OTHER_ACCOUNT,
                'CredentialType' => '4B5943',
            ],
            'CredentialDelete' => [
                'Subject' => self::OTHER_ACCOUNT,
                'CredentialType' => '4B5943',
            ],

            // PermissionedDomains
            'PermissionedDomainSet' => [
                'AcceptedCredentials' => [
                    ['Credential' => [
                        'Issuer' => self::OTHER_ACCOUNT,
                        'CredentialType' => '4B5943',
                    ]]
                ],
            ],
            'PermissionedDomainDelete' => ['DomainID' => self::HASH],

            // PermissionedDEX
            'OfferCreate' => [
                'TakerGets' => '1000000',
                'TakerPays' => $usdAmount,
                'DomainID' => self::HASH,
            ],

            // Prio B
            'AMMClawback' => [
                'Holder' => self::OTHER_ACCOUNT,
                'Asset' => $usd,
                'Asset2' => ['currency' => 'XRP'],
                'Amount' => $usdAmount,
            ],
            'NFTokenModify' => [
                'NFTokenID' => self::HASH,
                'Owner' => self::OTHER_ACCOUNT,
                'URI' => '68747470',
            ],
            'DelegateSet' => [
                'Authorize' => self::OTHER_ACCOUNT,
                'Permissions' => [
                    ['Permission' => ['PermissionValue' => 'Payment']],
                    ['Permission' => ['PermissionValue' => 'AccountDomainSet']],
                ],
            ],

            'Batch' => [
                'Flags' => 65536,
                'RawTransactions' => [
                    ['RawTransaction' => [
                        'TransactionType' => 'Payment',
                        'Account' => self::ACCOUNT,
                        'Destination' => self::OTHER_ACCOUNT,
                        'Amount' => '1000',
                        'Fee' => '0',
                        'Sequence' => 2,
                        'SigningPubKey' => '',
                        'Flags' => 1073741824,
                    ]]
                ],
            ],

            // Types that used to be broken in the codec
            'AMMCreate' => [
                'Amount' => '1000',
                'Amount2' => $usdAmount,
                'TradingFee' => 10,
            ],
            'AMMDelete' => ['Asset' => $usd, 'Asset2' => ['currency' => 'XRP']],
            'AMMWithdraw' => [
                'Asset' => $usd,
                'Asset2' => ['currency' => 'XRP'],
                'LPTokenIn' => [
                    'currency' => 'B3813FCAB4EE68B3D0D735D6849465A9113EE048',
                    'issuer' => self::OTHER_ACCOUNT,
                    'value' => '1000',
                ],
            ],
            'NFTokenCreateOffer' => [
                'NFTokenID' => self::HASH,
                'Amount' => '1000',
            ],
            'PaymentChannelCreate' => [
                'Amount' => '1000',
                'Destination' => self::OTHER_ACCOUNT,
                'SettleDelay' => 100,
                'PublicKey' => '0330E7FC9D56BB25D6893BA3F317AE5BCF33B3291BD63DB32654A313222F7FD020',
                'CancelAfter' => 533171558,
            ],
            'XChainCreateBridge' => [
                'XChainBridge' => [
                    'LockingChainDoor' => self::ACCOUNT,
                    'LockingChainIssue' => ['currency' => 'XRP'],
                    'IssuingChainDoor' => self::OTHER_ACCOUNT,
                    'IssuingChainIssue' => ['currency' => 'XRP'],
                ],
                'SignatureReward' => '200',
            ],
            'CheckCancel' => ['CheckID' => self::HASH],

            // MPT amounts and credential lists on existing types
            'Payment' => [
                'Destination' => self::OTHER_ACCOUNT,
                'Amount' => ['mpt_issuance_id' => self::MPT_ISSUANCE_ID, 'value' => '10'],
                'CredentialIDs' => [self::HASH],
            ],
            'EscrowCreate' => [
                'Destination' => self::OTHER_ACCOUNT,
                'Amount' => $usdAmount,
                'FinishAfter' => 533171558,
            ],
        ];

        $provided = [];
        foreach ($cases as $type => $fields) {
            $provided[$type] = [$type, $fields];
        }

        return $provided;
    }

    #[DataProvider('transactionProvider')]
    public function testRoundtrip(string $type, array $fields): void
    {
        $class = 'Hardcastle\\XRPL_PHP\\Models\\Transaction\\TransactionTypes\\' . $type;
        $this->assertTrue(class_exists($class), "Model {$class} is missing");

        $tx = array_merge(
            ['Account' => self::ACCOUNT, 'Fee' => '10', 'Sequence' => 1, 'TransactionType' => $type],
            $fields
        );

        $model = new $class($tx);
        $hex = $this->binaryCodec->encode(json_encode($model->toArray()));

        $this->assertEquals(
            self::normalize($model->toArray()),
            self::normalize($this->binaryCodec->decode($hex))
        );
    }

    /**
     * Signing goes through the same serializer, so every type has to produce a
     * blob that verifies against its own signature.
     */
    #[DataProvider('transactionProvider')]
    public function testSigning(string $type, array $fields): void
    {
        $wallet = Wallet::fromSeed(self::SEED);

        $tx = array_merge(
            [
                'Account' => $wallet->getAddress(),
                'Fee' => '12',
                'Sequence' => 1,
                'LastLedgerSequence' => 100,
                'TransactionType' => $type,
            ],
            $fields
        );

        // The bridge door has to be an account of this transaction
        if ($type === 'XChainCreateBridge') {
            $tx['XChainBridge']['LockingChainDoor'] = $wallet->getAddress();
        }

        $signed = $wallet->sign($tx);

        $this->assertTrue($wallet->verifyTransaction($signed['tx_blob']));
        $this->assertEquals(
            $type,
            $this->binaryCodec->decode($signed['tx_blob'])['TransactionType']
        );
    }

    /**
     * A misspelled transaction type used to be encoded as the ordinal 0, which
     * silently turned it into a Payment.
     */
    public function testUnknownTransactionTypeIsRejected(): void
    {
        $this->expectExceptionMessage('Unknown TransactionType: AmmCreate');

        $this->binaryCodec->encode(json_encode([
            'TransactionType' => 'AmmCreate',
            'Account' => self::ACCOUNT,
            'Fee' => '10',
            'Sequence' => 1,
        ]));
    }

    private static function normalize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $normalized = array_map(self::normalize(...), $value);
        if (!array_is_list($normalized)) {
            ksort($normalized);
        }

        return $normalized;
    }
}
