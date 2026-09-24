<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Core\RippleBinaryCodec\Definitions;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions\Definitions;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions\FieldHeader;

/**
 * The bundled definitions.json has to be rippled 3.4.0.
 *
 * The file is what a 3.4.0 node reports through server_definitions, taken
 * verbatim, with the node's digest in "hash". The counts and the digest pin
 * the whole inventory; the named entries are the ones recent releases added,
 * renamed or dropped, so a regression names the culprit. Where a check has
 * to see the bundled file alone, without the Xahau entries getInstance()
 * merges in, it reads the file.
 */
final class Rippled340ParityTest extends TestCase
{
    private const ACCOUNT = 'rPT1Sjq2YGrBMTttX4GZHjKu9dyfzbpAYe';

    private const OTHER_ACCOUNT = 'rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh';

    private const HASH = 'ABABABABABABABABABABABABABABABABABABABABABABABABABABABABABABABAB';

    private const DEFINITIONS_PATH = __DIR__
        . '/../../../../src/Core/RippleBinaryCodec/Definitions/definitions.json';

    /**
     * The digest s1.ripple.com reported for its definitions on 2026-09-24,
     * running rippled 3.4.0.
     */
    private const RIPPLED_3_4_0_HASH = '1EA05B0FC11101F7C500BD0DAC794A8BC746A7FBA6250B75489603EB820E0FF5';

    private static function bundled(): array
    {
        return json_decode((string) file_get_contents(self::DEFINITIONS_PATH), true, 512, JSON_THROW_ON_ERROR);
    }

    public function testInventoryMatchesRippled340(): void
    {
        $raw = self::bundled();

        $this->assertSame(self::RIPPLED_3_4_0_HASH, $raw['hash']);
        $this->assertCount(31, $raw['TYPES']);
        $this->assertCount(32, $raw['LEDGER_ENTRY_TYPES']);
        $this->assertCount(83, $raw['TRANSACTION_TYPES']);
        $this->assertCount(195, $raw['TRANSACTION_RESULTS']);
        $this->assertCount(357, $raw['FIELDS']);
        $this->assertCount(83, $raw['TRANSACTION_FORMATS'], 'the 82 real types plus "common"; Invalid has no format');
        $this->assertCount(32, $raw['LEDGER_ENTRY_FORMATS']);
    }

    /**
     * Ledger entries, transaction types and result codes added in 3.3.0.
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
     * Fields added in 3.3.0 and 3.4.0, one per type they introduce to, plus
     * the rename.
     */
    public static function newFieldProvider(): array
    {
        return [
            'ImmutableFlags (was MutableFlags)' => ['ImmutableFlags', 'UInt32', 53],
            'SponsorFlags' => ['SponsorFlags', 'UInt32', 74],
            'SubscriptionDate (3.4.0)' => ['SubscriptionDate', 'UInt32', 75],
            'RedemptionDate (3.4.0)' => ['RedemptionDate', 'UInt32', 76],
            'SponseeNode' => ['SponseeNode', 'UInt64', 33],
            'ObjectID' => ['ObjectID', 'Hash256', 41],
            'FeeAmount' => ['FeeAmount', 'Amount', 32],
            'ZKProof' => ['ZKProof', 'Blob', 37],
            'Sponsor' => ['Sponsor', 'AccountID', 27],
            'RemainingOwnerCountDelta' => ['RemainingOwnerCountDelta', 'Int32', 2],
            'SponsorSignature' => ['SponsorSignature', 'STObject', 38],
            'LEVersion (3.4.0)' => ['LEVersion', 'UInt8', 6],
            'ContractResult (3.4.0)' => ['ContractResult', 'UInt8', 21],
            'VaultKind (3.4.0)' => ['VaultKind', 'UInt8', 22],
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
     * The fields ripple-binary-codec's main branch has beyond rippled 3.4.0.
     * A field that exists only on the rippled development branch can still be
     * renumbered before it ships, and nothing a 3.4.0 node sends carries it.
     */
    public static function beyond340Provider(): array
    {
        return [
            'IssuerKeyEpoch' => ['IssuerKeyEpoch'],
            'AuditorKeyEpoch' => ['AuditorKeyEpoch'],
            'IssuerKeyMirrorEpoch' => ['IssuerKeyMirrorEpoch'],
            'AuditorKeyMirrorEpoch' => ['AuditorKeyMirrorEpoch'],
        ];
    }

    #[DataProvider('beyond340Provider')]
    public function testNoFieldBeyond340(string $name): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Field {$name} not found");

        Definitions::getInstance()->getFieldHeaderFromName($name);
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
     * The Xahau Hook fields and tecHOOK_REJECTED are not XRP Ledger
     * definitions. They used to sit in the bundled file as a leftover of the
     * early xrpl.js definitions; hooksDefinitions.json carries all of them, so
     * the merged definitions still know every one. HookOn is the field where
     * that matters: the leftover (UInt64, nth 16) shadowed Xahau's definition
     * (Hash256, nth 20), so a SetHook encoded HookOn wrongly.
     */
    public function testHookDefinitionsComeFromXahauOnly(): void
    {
        $raw = self::bundled();
        $names = array_column($raw['FIELDS'], 0);

        $this->assertNotContains('HookOn', $names);
        $this->assertNotContains('Hooks', $names);
        $this->assertNotContains('EmittedTxn', $names);
        $this->assertArrayNotHasKey('tecHOOK_REJECTED', $raw['TRANSACTION_RESULTS']);

        $merged = Definitions::getInstance();
        $this->assertEquals(
            new FieldHeader($raw['TYPES']['Hash256'], 20),
            $merged->getFieldHeaderFromName('HookOn'),
            'HookOn has to be the Xahau field, not the leftover'
        );
        $this->assertEquals(153, $merged->mapSpecificFieldFromValue('TransactionResult', 'tecHOOK_REJECTED'));
    }

    /**
     * rippled keeps the value 198 reserved but no 3.4.0 node reports the
     * code; it is gone from the XRP Ledger file.
     */
    public function testDeprecatedDelegateCodeIsGone(): void
    {
        $this->assertArrayNotHasKey('tecNO_DELEGATE_PERMISSION', self::bundled()['TRANSACTION_RESULTS']);
    }

    /**
     * The types without a fixture in ripple-binary-codec, built by hand from
     * transactions.macro and ledger_entries.macro, plus the 3.4.0 fields on
     * the vault.
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
            'VaultCreate with the 3.4.0 fields' => [$common + [
                'TransactionType' => 'VaultCreate',
                'Asset' => ['currency' => 'XRP'],
                'VaultKind' => 1,
                'SubscriptionDate' => 800000000,
                'RedemptionDate' => 800100000,
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
            'MPTokenIssuanceSet with the Confidential MPT keys' => [$common + [
                'TransactionType' => 'MPTokenIssuanceSet',
                'MPTokenIssuanceID' => '000004C463C52827307480341125DA0577DEFC38405B0E3E',
                'IssuerEncryptionKey' => '02' . self::HASH,
                'AuditorEncryptionKey' => '03' . self::HASH,
            ]],
        ];
    }

    #[DataProvider('roundtripProvider')]
    public function testRoundtrip(array $object): void
    {
        $codec = new BinaryCodec();

        $decoded = $codec->decode($codec->encode(json_encode($object, JSON_THROW_ON_ERROR)));

        ksort($object);
        ksort($decoded);
        $this->assertEquals($object, $decoded);
    }
}
