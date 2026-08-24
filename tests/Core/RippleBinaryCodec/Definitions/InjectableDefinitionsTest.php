<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Core\RippleBinaryCodec\Definitions;

use Exception;
use PHPUnit\Framework\TestCase;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions\Definitions;
use Hardcastle\XRPL_PHP\Utils\Hashes\HashLedger;
use Hardcastle\XRPL_PHP\Wallet\Wallet;

use function Hardcastle\XRPL_PHP\Sugar\getLastLedgerSequence;
use function Hardcastle\XRPL_PHP\Sugar\isAccountDelete;

/**
 * The codec has to work against definitions handed in from outside, so that a
 * package for another network (Xahau being the concrete case) can supply its
 * own definitions.json instead of the bundled one.
 *
 * The tests build an alternative set by remapping ordinals in the bundled
 * definitions. That is exactly what makes the two networks incompatible in
 * reality - Xahau assigns MPTokenIssuanceCreate the ordinal 63 where the XRP
 * Ledger assigns 54 - without pulling a second large fixture into the repo.
 */
final class InjectableDefinitionsTest extends TestCase
{
    private const ACCOUNT = 'rPT1Sjq2YGrBMTttX4GZHjKu9dyfzbpAYe';

    private const SEED = 'sEd7rnfWxwJmRditu2UpSsrZDRgtctn';

    private const DEFINITIONS_PATH = __DIR__
        . '/../../../../src/Core/RippleBinaryCodec/Definitions/definitions.json';

    /**
     * The bundled definitions with Payment moved from ordinal 0 to 99.
     */
    private static function alternativeDefinitions(): Definitions
    {
        $raw = json_decode(file_get_contents(self::DEFINITIONS_PATH), true);
        $raw['TRANSACTION_TYPES']['Payment'] = 99;

        return Definitions::fromArray($raw);
    }

    private static function payment(): array
    {
        return [
            'TransactionType' => 'Payment',
            'Account' => self::ACCOUNT,
            'Destination' => 'rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh',
            'Amount' => '1000',
            'Fee' => '10',
            'Sequence' => 1,
        ];
    }

    private static function ordinalOf(string $hex): int
    {
        // A transaction starts with the TransactionType field: 12 followed by
        // the UInt16 ordinal.
        return hexdec(substr($hex, 2, 4));
    }

    public function testInjectedDefinitionsAreUsedForEncoding(): void
    {
        $default = (new BinaryCodec())->encode(json_encode(self::payment()));
        $custom = (new BinaryCodec(self::alternativeDefinitions()))->encode(json_encode(self::payment()));

        $this->assertEquals(0, self::ordinalOf($default));
        $this->assertEquals(99, self::ordinalOf($custom));
    }

    public function testInjectedDefinitionsAreUsedForDecoding(): void
    {
        $definitions = self::alternativeDefinitions();
        $hex = (new BinaryCodec($definitions))->encode(json_encode(self::payment()));

        $this->assertEquals(
            'Payment',
            (new BinaryCodec($definitions))->decode($hex)['TransactionType']
        );
    }

    /**
     * The definitions have to survive the whole decode, including the nested
     * objects an STArray produces.
     */
    public function testDefinitionsTravelIntoNestedObjects(): void
    {
        $definitions = self::alternativeDefinitions();
        $codec = new BinaryCodec($definitions);

        $tx = self::payment();
        $tx['Memos'] = [['Memo' => ['MemoData' => '48656C6C6F']]];

        $decoded = $codec->decode($codec->encode(json_encode($tx)));

        $this->assertEquals('48656C6C6F', $decoded['Memos'][0]['Memo']['MemoData']);
        $this->assertEquals('Payment', $decoded['TransactionType']);
    }

    /**
     * Signing goes through the codec, so a wallet built with other definitions
     * has to produce a blob encoded against them.
     */
    public function testWalletUsesInjectedDefinitions(): void
    {
        $default = Wallet::fromSeed(self::SEED);
        $custom = Wallet::fromSeed(self::SEED, self::alternativeDefinitions());

        $tx = self::payment();
        $tx['Account'] = $default->getAddress();

        $this->assertEquals(0, self::ordinalOf($default->sign($tx)['tx_blob']));
        $this->assertEquals(99, self::ordinalOf($custom->sign($tx)['tx_blob']));

        $this->assertTrue($custom->verifyTransaction($custom->sign($tx)['tx_blob']));
    }

    /**
     * An injected set must not leak into the shared default instance.
     */
    public function testDefaultInstanceIsNotAffected(): void
    {
        self::alternativeDefinitions();

        $this->assertEquals(
            0,
            Definitions::getInstance()->mapSpecificFieldFromValue('TransactionType', 'Payment')
        );
        $this->assertEquals(
            0,
            self::ordinalOf((new BinaryCodec())->encode(json_encode(self::payment())))
        );
    }

    public function testFromFile(): void
    {
        $definitions = Definitions::fromFile(self::DEFINITIONS_PATH);

        $this->assertEquals(
            0,
            $definitions->mapSpecificFieldFromValue('TransactionType', 'Payment')
        );
    }

    public function testFromFileRejectsMissingFile(): void
    {
        $this->expectExceptionMessage('Definitions file not found');

        Definitions::fromFile(__DIR__ . '/does-not-exist.json');
    }

    public function testRejectsIncompleteDefinitions(): void
    {
        $this->expectExceptionMessage('Definitions are missing the section FIELDS');

        Definitions::fromArray([
            'TYPES' => [],
            'LEDGER_ENTRY_TYPES' => [],
            'TRANSACTION_RESULTS' => [],
            'TRANSACTION_TYPES' => [],
        ]);
    }


    /**
     * hashSignedTx() produces the hashed blob itself when given an array, so
     * the definitions decide the result. Getting this wrong yields a valid
     * looking but wrong hash, with nothing to indicate it.
     */
    public function testHashSignedTxHonoursDefinitions(): void
    {
        $wallet = Wallet::fromSeed(self::SEED);

        $tx = self::payment();
        $tx['Account'] = $wallet->getAddress();
        $signed = $wallet->sign($tx);
        $txObject = (new BinaryCodec())->decode($signed['tx_blob']);

        $default = HashLedger::hashSignedTx($txObject);
        $custom = HashLedger::hashSignedTx($txObject, self::alternativeDefinitions());

        $this->assertEquals($signed['hash'], $default);
        $this->assertNotEquals($default, $custom, 'other definitions have to produce a different blob');
    }

    /**
     * Definitions with a field the bundled set does not know, the way a Xahau
     * specific field behaves.
     */
    private static function definitionsWithExtraField(): Definitions
    {
        $raw = json_decode(file_get_contents(self::DEFINITIONS_PATH), true);
        $raw['FIELDS'][] = ['NetworkSpecificBlob', [
            'nth' => 32,
            'isVLEncoded' => true,
            'isSerialized' => true,
            'isSigningField' => true,
            'type' => 'Blob',
        ]];

        return Definitions::fromArray($raw);
    }

    /**
     * Wallet::sign() hashes the blob it just produced. Hashing decodes it to
     * check the signature is present, and decoding resolves every field - so a
     * transaction carrying a field only the injected definitions know would
     * throw there if the wallet hashed through the default set instead.
     */
    public function testWalletSignsTransactionWithNetworkSpecificField(): void
    {
        $definitions = self::definitionsWithExtraField();
        $wallet = Wallet::fromSeed(self::SEED, $definitions);

        $tx = self::payment();
        $tx['Account'] = $wallet->getAddress();
        $tx['NetworkSpecificBlob'] = 'DEADBEEF';

        $signed = $wallet->sign($tx);

        $this->assertTrue($wallet->verifyTransaction($signed['tx_blob']));
        $this->assertEquals(
            'DEADBEEF',
            (new BinaryCodec($definitions))->decode($signed['tx_blob'])['NetworkSpecificBlob']
        );

        // The bundled definitions cannot read that field at all
        $this->expectException(Exception::class);
        (new BinaryCodec())->decode($signed['tx_blob']);
    }

    /**
     * Decoding resolves every field in the blob, so these helpers need the
     * definitions the transaction was built with - not just for the field they
     * read, but to get through the decode at all.
     */
    public function testSugarHelpersAcceptDefinitions(): void
    {
        $definitions = self::alternativeDefinitions();
        $tx = self::payment();
        $tx['LastLedgerSequence'] = 4321;
        $blob = (new BinaryCodec($definitions))->encode(json_encode($tx));

        $this->assertEquals(4321, getLastLedgerSequence($blob, $definitions));
        $this->assertFalse(isAccountDelete($blob, $definitions));
    }
}
