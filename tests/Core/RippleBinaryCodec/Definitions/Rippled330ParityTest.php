<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Core\RippleBinaryCodec\Definitions;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions\Definitions;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions\FieldHeader;

/**
 * The bundled definitions.json has to match rippled 3.3.0.
 *
 * The inventory below was taken from the protocol macros of the rippled 3.3.0
 * tag (ledger_entries.macro, transactions.macro, sfields.macro, TER.h) and
 * cross-checked against the definitions.json of ripple-binary-codec, which
 * agrees with them. The counts pin the whole inventory; the named entries are
 * the ones 3.3.0 added or renamed, so a regression names the culprit.
 */
final class Rippled330ParityTest extends TestCase
{
    private const ACCOUNT = 'rPT1Sjq2YGrBMTttX4GZHjKu9dyfzbpAYe';

    private const OTHER_ACCOUNT = 'rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh';

    private const HASH = 'ABABABABABABABABABABABABABABABABABABABABABABABABABABABABABABABAB';

    private const DEFINITIONS_PATH = __DIR__
        . '/../../../../src/Core/RippleBinaryCodec/Definitions/definitions.json';

    /**
     * The bundled file alone, without the Xahau entries getInstance() merges in.
     */
    private static function bundled(): array
    {
        return json_decode(file_get_contents(self::DEFINITIONS_PATH), true);
    }

    public function testInventoryMatchesRippled330(): void
    {
        $raw = self::bundled();

        // 31 types, 32 ledger entries and 83 transaction types are exactly what
        // rippled 3.3.0 defines. The 197 result codes are the 195 of
        // ripple-binary-codec plus tecNO_DELEGATE_PERMISSION, which rippled
        // 3.3.0 keeps as deprecated, and tecHOOK_REJECTED, whose value rippled
        // reserves for Xahau. The 390 fields are the 361 of 3.3.0 plus the 29
        // Hook fields the file has always carried for Xahau.
        $this->assertCount(31, $raw['TYPES']);
        $this->assertCount(32, $raw['LEDGER_ENTRY_TYPES']);
        $this->assertCount(83, $raw['TRANSACTION_TYPES']);
        $this->assertCount(197, $raw['TRANSACTION_RESULTS']);
        $this->assertCount(390, $raw['FIELDS']);
    }

    /**
     * Ledger entries and transaction types new in 3.3.0.
     */
    public static function newOrdinalProvider(): array
    {
        return [
            'Sponsorship ledger entry' => ['LedgerEntryType', 'Sponsorship', 144],
            'ConfidentialMPTConvert' => ['TransactionType', 'ConfidentialMPTConvert', 85],
            'ConfidentialMPTMergeInbox' => ['TransactionType', 'ConfidentialMPTMergeInbox', 86],
            'ConfidentialMPTConvertBack' => ['TransactionType', 'ConfidentialMPTConvertBack', 87],
            'ConfidentialMPTSend' => ['TransactionType', 'ConfidentialMPTSend', 88],
            'ConfidentialMPTClawback' => ['TransactionType', 'ConfidentialMPTClawback', 89],
            'SponsorshipTransfer' => ['TransactionType', 'SponsorshipTransfer', 90],
            'SponsorshipSet' => ['TransactionType', 'SponsorshipSet', 91],
            'temBAD_CIPHERTEXT' => ['TransactionResult', 'temBAD_CIPHERTEXT', -248],
            'tefNO_DST_PARTIAL' => ['TransactionResult', 'tefNO_DST_PARTIAL', -177],
            'tefBAD_PATH_COUNT' => ['TransactionResult', 'tefBAD_PATH_COUNT', -176],
            'terNO_PERMISSION' => ['TransactionResult', 'terNO_PERMISSION', -83],
            'tecBAD_PROOF' => ['TransactionResult', 'tecBAD_PROOF', 199],
            'tecNO_SPONSOR_PERMISSION' => ['TransactionResult', 'tecNO_SPONSOR_PERMISSION', 200],
        ];
    }

    #[DataProvider('newOrdinalProvider')]
    public function testNewOrdinalResolvesBothWays(string $field, string $name, int $ordinal): void
    {
        $definitions = Definitions::getInstance();

        $this->assertEquals($ordinal, $definitions->mapSpecificFieldFromValue($field, $name));
        $this->assertEquals($name, $definitions->mapValueToSpecificField($field, $ordinal));
    }

    /**
     * Fields new in 3.3.0, one per type they introduce to, plus the rename.
     */
    public static function newFieldProvider(): array
    {
        return [
            'ImmutableFlags (was MutableFlags)' => ['ImmutableFlags', 'UInt32', 53],
            'SponsorFlags' => ['SponsorFlags', 'UInt32', 74],
            'SponseeNode' => ['SponseeNode', 'UInt64', 33],
            'ObjectID' => ['ObjectID', 'Hash256', 41],
            'FeeAmount' => ['FeeAmount', 'Amount', 32],
            'ZKProof' => ['ZKProof', 'Blob', 37],
            'Sponsor' => ['Sponsor', 'AccountID', 27],
            'RemainingOwnerCountDelta' => ['RemainingOwnerCountDelta', 'Int32', 2],
            'SponsorSignature' => ['SponsorSignature', 'STObject', 38],
            'VaultKind' => ['VaultKind', 'UInt8', 22],
        ];
    }

    #[DataProvider('newFieldProvider')]
    public function testNewFieldHasItsHeader(string $name, string $type, int $nth): void
    {
        $definitions = Definitions::getInstance();
        $typeCode = self::bundled()['TYPES'][$type];

        $this->assertEquals(
            new FieldHeader($typeCode, $nth),
            $definitions->getFieldHeaderFromName($name)
        );
        $this->assertEquals($name, $definitions->getFieldNameFromHeader(new FieldHeader($typeCode, $nth)));
    }

    /**
     * rippled 3.3.0 renamed sfMutableFlags to sfImmutableFlags, keeping the
     * ordinal. The old name is gone, so a transaction built with it fails
     * loudly instead of encoding under a name no node accepts.
     */
    public function testMutableFlagsIsGone(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Field MutableFlags not found');

        Definitions::getInstance()->getFieldHeaderFromName('MutableFlags');
    }

    /**
     * The types without a fixture in ripple-binary-codec, built by hand from
     * the 3.3.0 transactions.macro and ledger_entries.macro.
     */
    public static function roundtripProvider(): array
    {
        $common = [
            'Account' => self::ACCOUNT,
            'Fee' => '12',
            'Sequence' => 1,
            'SigningPubKey' => '',
        ];

        return [
            'SponsorshipSet' => [$common + [
                'TransactionType' => 'SponsorshipSet',
                'CounterpartySponsor' => self::OTHER_ACCOUNT,
                'Sponsee' => self::OTHER_ACCOUNT,
                'FeeAmountDelta' => '1000',
                'MaxFee' => '5000',
                'RemainingOwnerCountDelta' => -3,
            ]],
            'SponsorshipTransfer' => [$common + [
                'TransactionType' => 'SponsorshipTransfer',
                'ObjectID' => self::HASH,
                'Sponsee' => self::OTHER_ACCOUNT,
            ]],
            'Sponsorship ledger entry' => [[
                'LedgerEntryType' => 'Sponsorship',
                'Flags' => 0,
                'PreviousTxnID' => self::HASH,
                'PreviousTxnLgrSeq' => 100,
                'Owner' => self::ACCOUNT,
                'Sponsee' => self::OTHER_ACCOUNT,
                'FeeAmount' => '1000',
                'MaxFee' => '5000',
                'RemainingOwnerCount' => 7,
                'OwnerNode' => '0000000000000000',
                'SponseeNode' => '0000000000000001',
            ]],
            'MPTokenIssuanceCreate with ImmutableFlags' => [$common + [
                'TransactionType' => 'MPTokenIssuanceCreate',
                'AssetScale' => 2,
                'MaximumAmount' => '100000000',
                'ImmutableFlags' => 96,
            ]],
        ];
    }

    #[DataProvider('roundtripProvider')]
    public function testRoundtrip(array $object): void
    {
        $codec = new BinaryCodec();

        $decoded = $codec->decode($codec->encode(json_encode($object)));

        ksort($object);
        ksort($decoded);
        $this->assertEquals($object, $decoded);
    }
}
