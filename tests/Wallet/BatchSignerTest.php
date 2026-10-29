<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Wallet;

use Exception;
use PHPUnit\Framework\TestCase;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use Hardcastle\XRPL_PHP\Core\RippleKeyPairs\Ed25519KeyPairService;
use Hardcastle\XRPL_PHP\Exceptions\ValidationException;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\BatchFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\GlobalFlags;
use Hardcastle\XRPL_PHP\Utils\Hashes\HashLedger;
use Hardcastle\XRPL_PHP\Wallet\Wallet;

/**
 * Batch signing (XLS-56, Batch V1.1) against xrpl.js.
 *
 * The Batch, the wallets and every expected signature are those of
 * packages/xrpl/test/wallet/batchSigner.test.ts; the payload bytes and the
 * inner transaction IDs were produced with the released xrpl 5.3.0 and
 * ripple-binary-codec 2.11.0. secp256k1 signatures are deterministic
 * (RFC 6979) and Ed25519 ones always, so they compare byte for byte.
 *
 * xrpl.js derives one of its wallets from a secp256k1 seed with the Ed25519
 * algorithm forced; fromSeed() here follows the seed, so that wallet is
 * built from the Ed25519 key pair directly.
 */
final class BatchSignerTest extends TestCase
{
    private const SEED = 'spkcsko6Ag3RbCSVXV2FJ8Pd4Zac1';

    private const SUBMIT_SEED = 'sEd7HmQFsoyj5TAm6d98gytM9LJA1MF';

    private const REGKEY_SEED = 'sEdStM1pngFcLQqVfH3RQcg2Qr6ov9e';

    private const OUTER = 'rJCxK2hX9tDMzbnn3cg1GU2g19Kfmhzxkp';

    private const ED = 'rJy554HmWFFJQGnRfZuoo8nV97XSMq77h7';

    private const SECP = 'rPMh7Pi9ct699iZUTWaytJUoHcJ7cgyziK';

    private const REGKEY = 'rwRNeznwHzdfYeKWpevYmax2NSDioyeEtT';

    private const INNER_IDS = [
        'FCCBD0F8F72050FE0CFC7AC5686EBDE93A8AAFFF5C60BA22957099DF484BD6CA',
        '9EDF5DB29F536DD3919037F1E8A72B040D075571A10C9000294C57B5ECEEA791',
    ];

    private const PAYLOAD_PREFIX = '42434800'
        . 'C1D81FB31C42392BA1570431F1CBCBEEBBEF50E1'   // outer Account
        . '00000000'                                   // sequence value
        . '00000001'                                   // Flags
        . '00000002'                                   // inner count
        . 'FCCBD0F8F72050FE0CFC7AC5686EBDE93A8AAFFF5C60BA22957099DF484BD6CA'
        . '9EDF5DB29F536DD3919037F1E8A72B040D075571A10C9000294C57B5ECEEA791';

    private const SECP_SIGNATURE = '304502210098890858AA57D6515D7C523FE076FA97BFA87DA666A87B4A7CF44249181DC1DC02201B90E513FE2F45D41FB31850F463C0ECBA8F5126B1AF431B67C4004CA0DD8042';

    private const ED_SIGNATURE = '27B496F0C1F2C4789A0E6CF25265069980190C786053CF5D6C066C07E21D632A6EB87C56275109A8542EEDE782FDC5591EA51FAF28C3FCFCF35BCE960F1D8601';

    private const REGKEY_FOR_ED_SIGNATURE = '046315C731DF089E08EB6662251F12B22938ED462F66BC561A847A87DF6B3C9AC811D9EC5971EDEC2BA96C959BDE883CD838B7EF6460A47AD9B71518F1A2A00B';

    private const REGKEY_MULTISIGN_SIGNATURE = '8FCA6C1056C2146DC13F4D10BA297335A82F562D837FA3C65D75DCDC87540F61428B7370FCC1DE4D83B6FA1A00A18CD9283E7B08089091ED84CC3E4A8B43F00F';

    private const REGKEY_MULTISIGN_AS_OUTER_SIGNATURE = 'D80D4195BF67D5CB12CA225D04DA4D00AC77250803671E09DF61F1695A831FAD6BF820F335DD2D8CFE16DA55CFC2E64AEC8A1429524E6CDB6C36B7AEA717C700';

    private const SECP_TICKET_SIGNATURE = '3045022100DAE7E156F64899D27EB1464730110F66464458EF873E006A2A781E4F998D1CB802200B5D52C2B87CC9E7C67E28237C205DC96A24477DB37BF34E7D4F8383D0A35272';

    private static function batch(): array
    {
        return [
            'Account' => self::OUTER,
            'Flags' => 1,
            'TransactionType' => 'Batch',
            'RawTransactions' => [
                ['RawTransaction' => [
                    'Account' => self::ED,
                    'Flags' => GlobalFlags::tfInnerBatchTxn,
                    'Amount' => '5000000',
                    'Destination' => self::SECP,
                    'Fee' => '0',
                    'Sequence' => 215,
                    'SigningPubKey' => '',
                    'TransactionType' => 'Payment',
                ]],
                ['RawTransaction' => [
                    'Account' => self::SECP,
                    'Flags' => GlobalFlags::tfInnerBatchTxn,
                    'Amount' => '1000000',
                    'Destination' => self::OUTER,
                    'Fee' => '0',
                    'Sequence' => 470,
                    'SigningPubKey' => '',
                    'TransactionType' => 'Payment',
                ]],
            ],
        ];
    }

    private static function secpWallet(): Wallet
    {
        return Wallet::fromSeed(self::SEED);
    }

    private static function edWallet(): Wallet
    {
        $keyPair = Ed25519KeyPairService::getInstance()->deriveKeyPair(self::SEED);

        return new Wallet($keyPair->getPublicKey(), $keyPair->getPrivateKey(), null);
    }

    public function testTheWalletsAreTheOnesOfXrplJs(): void
    {
        $this->assertSame(self::SECP, self::secpWallet()->getAddress());
        $this->assertSame(self::ED, self::edWallet()->getAddress());
        $this->assertSame(self::REGKEY, Wallet::fromSeed(self::REGKEY_SEED)->getAddress());
        $this->assertSame(self::OUTER, Wallet::fromSeed(self::SUBMIT_SEED)->getAddress());
    }

    public function testInnerTransactionIdsAreTheUnsignedTransactionHashes(): void
    {
        $codec = new BinaryCodec();

        $this->assertSame(self::INNER_IDS, $codec->innerTransactionIds(self::batch()));
        $this->assertSame(
            self::INNER_IDS[0],
            HashLedger::hashTx(self::batch()['RawTransactions'][0]['RawTransaction'])
        );
    }

    public function testHashSignedTxStillRefusesAnUnsignedTransaction(): void
    {
        $this->expectExceptionMessage('must be signed');

        HashLedger::hashSignedTx(self::batch()['RawTransactions'][0]['RawTransaction']);
    }

    public function testEncodeForSigningBatch(): void
    {
        $codec = new BinaryCodec();

        $this->assertSame(
            self::PAYLOAD_PREFIX . 'F51DFC2A09D62CBBA1DFBDD4691DAC96AD98B90F',
            $codec->encodeForSigningBatch(self::batch(), self::SECP)
        );
        $this->assertSame(
            self::PAYLOAD_PREFIX
                . 'C53440AC6F65A0CF326B76E0EE81D9B4D7A22C5F'   // BatchSigner rJy554...
                . '674E837FE8BE47B63BC2040626100FA3F071AF6A',  // signer rwRNez...
            $codec->encodeForSigningBatch(self::batch(), self::ED, self::REGKEY)
        );
    }

    public function testTheSequenceValueIsTheTicketWhenSequenceIsZero(): void
    {
        $batch = self::batch();
        $batch['Sequence'] = 0;
        $batch['TicketSequence'] = 777;
        $batch['Flags'] = BatchFlags::tfAllOrNothing;

        $this->assertSame(777, BinaryCodec::batchSequenceValue($batch));
        $this->assertSame(215, BinaryCodec::batchSequenceValue(['Sequence' => 215, 'TicketSequence' => 9]));
        $this->assertSame(0, BinaryCodec::batchSequenceValue([]));

        $payload = (new BinaryCodec())->encodeForSigningBatch($batch, self::SECP);
        $this->assertStringStartsWith('42434800C1D81FB31C42392BA1570431F1CBCBEEBBEF50E1' . '00000309' . '00010000', $payload);

        $signed = self::secpWallet()->signBatch($batch);
        $this->assertSame(self::SECP_TICKET_SIGNATURE, $signed['BatchSigners'][0]['BatchSigner']['TxnSignature']);
    }

    public function testSignsLikeXrplJsWithSecp256k1(): void
    {
        $signed = self::secpWallet()->signBatch(self::batch());

        $this->assertSame([
            ['BatchSigner' => [
                'Account' => self::SECP,
                'SigningPubKey' => '02691AC5AE1C4C333AE5DF8A93BDC495F0EEBFC6DB0DA7EB6EF808F3AFC006E3FE',
                'TxnSignature' => self::SECP_SIGNATURE,
            ]],
        ], $signed['BatchSigners']);
        $this->assertSame(self::batch()['RawTransactions'], $signed['RawTransactions'], 'the inner transactions are untouched');
    }

    public function testSignsLikeXrplJsWithEd25519(): void
    {
        $signed = self::edWallet()->signBatch(self::batch());

        $this->assertSame(self::ED, $signed['BatchSigners'][0]['BatchSigner']['Account']);
        $this->assertSame(self::ED_SIGNATURE, $signed['BatchSigners'][0]['BatchSigner']['TxnSignature']);
    }

    public function testARegularKeySignsForAnotherAccount(): void
    {
        $signed = Wallet::fromSeed(self::REGKEY_SEED)->signBatch(self::batch(), self::ED);

        $this->assertSame([
            ['BatchSigner' => [
                'Account' => self::ED,
                'SigningPubKey' => 'ED37D3F048B7F1E680B0A97F70C7843160B9F25D6398D07E68B9A2C83AA8E1B156',
                'TxnSignature' => self::REGKEY_FOR_ED_SIGNATURE,
            ]],
        ], $signed['BatchSigners']);
    }

    public function testMultisignsAsItself(): void
    {
        $signed = Wallet::fromSeed(self::REGKEY_SEED)->signBatch(self::batch(), self::ED, true);

        $this->assertSame([
            ['BatchSigner' => [
                'Account' => self::ED,
                'Signers' => [
                    ['Signer' => [
                        'Account' => self::REGKEY,
                        'SigningPubKey' => 'ED37D3F048B7F1E680B0A97F70C7843160B9F25D6398D07E68B9A2C83AA8E1B156',
                        'TxnSignature' => self::REGKEY_MULTISIGN_SIGNATURE,
                    ]],
                ],
            ]],
        ], $signed['BatchSigners']);
    }

    public function testMultisignsAsAnotherSignerAccount(): void
    {
        $signed = Wallet::fromSeed(self::REGKEY_SEED)->signBatch(self::batch(), self::ED, self::OUTER);

        $signer = $signed['BatchSigners'][0]['BatchSigner']['Signers'][0]['Signer'];
        $this->assertSame(self::OUTER, $signer['Account']);
        $this->assertSame(self::REGKEY_MULTISIGN_AS_OUTER_SIGNATURE, $signer['TxnSignature']);
    }

    public function testTheSignerAccountsAreTheInnerAccountsOrTheirDelegates(): void
    {
        $this->assertSame([self::ED, self::SECP], Wallet::batchSignerAccounts(self::batch()));

        $delegated = self::batch();
        $delegated['RawTransactions'][0]['RawTransaction']['Delegate'] = self::REGKEY;

        $this->assertSame([self::REGKEY, self::SECP], Wallet::batchSignerAccounts($delegated));

        // The delegate signs, the account holder cannot
        $signed = Wallet::fromSeed(self::REGKEY_SEED)->signBatch($delegated);
        $this->assertSame(self::REGKEY, $signed['BatchSigners'][0]['BatchSigner']['Account']);

        $this->expectException(ValidationException::class);
        self::edWallet()->signBatch($delegated);
    }

    public function testRefusesAnAccountWithoutAnInnerTransaction(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('has no inner transaction to sign for');

        Wallet::generate()->signBatch(self::batch());
    }

    public function testRefusesAnythingButABatch(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('needs a Batch');

        self::secpWallet()->signBatch(self::batch()['RawTransactions'][0]['RawTransaction']);
    }

    public function testRefusesAnIncompleteBatch(): void
    {
        $batch = self::batch();
        unset($batch['Flags']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Account and Flags');

        self::secpWallet()->signBatch($batch);
    }

    /**
     * The Batch of xrpl.js's combineBatchSigners tests.
     */
    private static function sequencedBatch(): array
    {
        $batch = self::batch();
        $batch['Flags'] = BatchFlags::tfAllOrNothing;
        $batch['Sequence'] = 215;
        $batch['LastLedgerSequence'] = 14973;
        $batch['NetworkID'] = 21336;

        return $batch;
    }

    public function testCombinesAndSortsTheSignersByAccount(): void
    {
        $bySecp = self::secpWallet()->signBatch(self::sequencedBatch());
        $byEd = self::edWallet()->signBatch(self::sequencedBatch());

        $expected = array_merge($byEd['BatchSigners'], $bySecp['BatchSigners']);

        $this->assertSame($expected, Wallet::combineBatchSigners([$bySecp, $byEd])['BatchSigners']);
        $this->assertSame($expected, Wallet::combineBatchSigners([$byEd, $bySecp])['BatchSigners']);

        // rJy554... (C53440AC...) sorts before rPMh7P... (F51DFC2A...) numerically
        $this->assertSame(self::ED, $expected[0]['BatchSigner']['Account']);
        $this->assertSame(
            '93BC0B856D4A5A8C51955BF2C9D33BD06B67D8EC3569A7A8290E0D14F5AE2882B86DA9F79B07C878D976FD0D376852407936B79B8BCB8840903675C853EE7205',
            $expected[0]['BatchSigner']['TxnSignature'],
            'the Ed25519 signature over the sequenced Batch, as xrpl.js computes it'
        );
    }

    public function testCombinesSerializedBatches(): void
    {
        $codec = new BinaryCodec();
        $bySecp = self::secpWallet()->signBatch(self::sequencedBatch());
        $byEd = self::edWallet()->signBatch(self::sequencedBatch());

        $combined = Wallet::combineBatchSigners([$codec->encode($bySecp), $codec->encode($byEd)]);

        $this->assertCount(2, $combined['BatchSigners']);
        $this->assertSame(self::ED, $combined['BatchSigners'][0]['BatchSigner']['Account']);
        $this->assertSame(self::SECP, $combined['BatchSigners'][1]['BatchSigner']['Account']);
        $this->assertSame(self::INNER_IDS, $codec->innerTransactionIds($combined), 'decoding kept the inner transactions intact');
    }

    public function testDropsTheOuterAccountAndDuplicates(): void
    {
        $batch = self::sequencedBatch();
        $batch['RawTransactions'][] = ['RawTransaction' => [
            'Account' => self::OUTER,
            'Flags' => GlobalFlags::tfInnerBatchTxn,
            'Amount' => '1000000',
            'Destination' => self::SECP,
            'Fee' => '0',
            'Sequence' => 470,
            'SigningPubKey' => '',
            'TransactionType' => 'Payment',
        ]];

        $byEd = self::edWallet()->signBatch($batch);
        $bySecp = self::secpWallet()->signBatch($batch);
        $byOuter = Wallet::fromSeed(self::SUBMIT_SEED)->signBatch($batch);

        $combined = Wallet::combineBatchSigners([$byEd, $bySecp, $byOuter, $bySecp]);

        $this->assertSame(array_merge($byEd['BatchSigners'], $bySecp['BatchSigners']), $combined['BatchSigners']);
    }

    public function testRefusesToCombineDifferentBatches(): void
    {
        $bySecp = self::secpWallet()->signBatch(self::sequencedBatch());

        $other = self::sequencedBatch();
        $other['Flags'] = BatchFlags::tfIndependent;
        $byEd = self::edWallet()->signBatch($other);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('have to be the same');

        Wallet::combineBatchSigners([$bySecp, $byEd]);
    }

    public function testRefusesToCombineASignedOrUnsignedBatch(): void
    {
        $bySecp = self::secpWallet()->signBatch(self::sequencedBatch());

        try {
            Wallet::combineBatchSigners([$bySecp, self::sequencedBatch()]);
            $this->fail('a Batch without BatchSigners has nothing to combine');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('BatchSigners', $e->getMessage());
        }

        $signedOuter = Wallet::fromSeed(self::SUBMIT_SEED)->sign($bySecp)['tx_blob'];
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must not be signed');

        Wallet::combineBatchSigners([$signedOuter, $bySecp]);
    }

    public function testRefusesAnEmptyList(): void
    {
        $this->expectException(ValidationException::class);

        Wallet::combineBatchSigners([]);
    }

    /**
     * The whole flow, ending in a signed outer transaction that decodes to
     * the same Batch, BatchSigners included.
     */
    public function testTheOuterAccountSignsTheCombinedBatch(): void
    {
        $combined = Wallet::combineBatchSigners([
            self::secpWallet()->signBatch(self::sequencedBatch()),
            self::edWallet()->signBatch(self::sequencedBatch()),
        ]);
        $combined['Fee'] = '40';

        $signed = Wallet::fromSeed(self::SUBMIT_SEED)->sign($combined);
        $decoded = (new BinaryCodec())->decode($signed['tx_blob']);

        $this->assertSame('Batch', $decoded['TransactionType']);
        $this->assertCount(2, $decoded['BatchSigners']);
        $this->assertSame(self::INNER_IDS, (new BinaryCodec())->innerTransactionIds($decoded));
        $this->assertSame($signed['hash'], HashLedger::hashSignedTx($signed['tx_blob']));
    }
}
