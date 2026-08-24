<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Core\RippleBinaryCodec\Definitions;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions\Definitions;

/**
 * Xahau reuses ordinals that the XRP Ledger assigns to different transaction
 * types and fields. hooksDefinitions.json used to be merged over the mainline
 * definitions with array_merge, so the Xahau entry won and decoding an XRPL
 * transaction produced Xahau names - silently, because the bytes stayed valid.
 *
 * These tests pin the resolution: the XRP Ledger wins, Xahau entries are only
 * added where the mainline has no entry of that name. They exist mainly as a
 * guard for future work on the Xahau side of the library.
 */
final class DefinitionsMergeTest extends TestCase
{
    private const ACCOUNT = 'rPT1Sjq2YGrBMTttX4GZHjKu9dyfzbpAYe';

    /** @psalm-suppress PropertyNotSetInConstructor */
    private BinaryCodec $binaryCodec;

    protected function setUp(): void
    {
        $this->binaryCodec = new BinaryCodec();
    }

    /**
     * The five transaction ordinals both networks use.
     */
    public static function collidingTransactionTypeProvider(): array
    {
        return [
            'XChainAddClaimAttestation vs URITokenMint' => ['XChainAddClaimAttestation', 45],
            'XChainAddAccountCreateAttestation vs URITokenBurn' => ['XChainAddAccountCreateAttestation', 46],
            'XChainModifyBridge vs URITokenBuy' => ['XChainModifyBridge', 47],
            'XChainCreateBridge vs URITokenCreateSellOffer' => ['XChainCreateBridge', 48],
            'DIDSet vs URITokenCancelSellOffer' => ['DIDSet', 49],
        ];
    }

    #[DataProvider('collidingTransactionTypeProvider')]
    public function testMainlineTransactionTypeWins(string $type, int $ordinal): void
    {
        $definitions = Definitions::getInstance();

        $this->assertEquals(
            $ordinal,
            $definitions->mapSpecificFieldFromValue('TransactionType', $type),
            "{$type} has to encode to ordinal {$ordinal}"
        );

        $this->assertEquals(
            $type,
            $definitions->mapValueToSpecificField('TransactionType', $ordinal),
            "ordinal {$ordinal} has to decode to {$type}, not to the Xahau type"
        );
    }

    /**
     * The field ordinals both networks use.
     */
    public static function collidingFieldProvider(): array
    {
        return [
            'AMMID vs ObjectID' => ['AMMID', 'Hash256'],
            'DomainID vs OfferID' => ['DomainID', 'Hash256'],
            'VaultID vs EscrowID' => ['VaultID', 'Hash256'],
            'ParentBatchID vs URITokenID' => ['ParentBatchID', 'Hash256'],
            'DIDDocument vs Blob' => ['DIDDocument', 'Blob'],
            'CredentialIDs vs HookNamespaces' => ['CredentialIDs', 'Vector256'],
            'LedgerFixType vs HookStateScale' => ['LedgerFixType', 'UInt16'],
        ];
    }

    #[DataProvider('collidingFieldProvider')]
    public function testMainlineFieldWins(string $fieldName, string $type): void
    {
        $definitions = Definitions::getInstance();
        $header = $definitions->getFieldHeaderFromName($fieldName);

        $this->assertEquals(
            $fieldName,
            $definitions->getFieldNameFromHeader($header),
            "{$type} field {$fieldName} has to resolve to itself, not to the Xahau field"
        );
    }

    /**
     * The whole point of the collision: this used to decode as
     * URITokenCreateSellOffer.
     */
    public function testXChainTransactionDecodesAsItself(): void
    {
        $tx = [
            'TransactionType' => 'XChainCreateBridge',
            'Account' => self::ACCOUNT,
            'Fee' => '10',
            'Sequence' => 1,
            'XChainBridge' => [
                'LockingChainDoor' => self::ACCOUNT,
                'LockingChainIssue' => ['currency' => 'XRP'],
                'IssuingChainDoor' => 'rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh',
                'IssuingChainIssue' => ['currency' => 'XRP'],
            ],
            'SignatureReward' => '200',
        ];

        $decoded = $this->binaryCodec->decode($this->binaryCodec->encode(json_encode($tx)));

        $this->assertEquals('XChainCreateBridge', $decoded['TransactionType']);
    }

    /**
     * Xahau-only types keep working; they are added, not overwritten.
     */
    public function testXahauOnlyTypesStillEncode(): void
    {
        foreach (['Invoke', 'ClaimReward', 'Import', 'URITokenMint'] as $type) {
            $hex = $this->binaryCodec->encode(json_encode([
                'TransactionType' => $type,
                'Account' => self::ACCOUNT,
                'Fee' => '10',
                'Sequence' => 1,
            ]));

            $this->assertStringStartsWith('12', $hex, "{$type} has to encode");
        }
    }

    /**
     * A DelegateSet permission is either a granular permission or a transaction
     * type ordinal incremented by one.
     */
    public function testDelegatablePermissions(): void
    {
        $definitions = Definitions::getInstance();

        $this->assertEquals(
            65540,
            $definitions->mapSpecificFieldFromValue('PermissionValue', 'AccountDomainSet')
        );
        $this->assertEquals(
            1,
            $definitions->mapSpecificFieldFromValue('PermissionValue', 'Payment')
        );
        $this->assertEquals(
            'AccountDomainSet',
            $definitions->mapValueToSpecificField('PermissionValue', 65540)
        );
    }

    /**
     * An unknown name used to be cast to the ordinal 0, which turned a
     * misspelled transaction type into a Payment.
     */
    public function testUnknownEnumValueThrows(): void
    {
        $this->expectExceptionMessage('Unknown TransactionType: NotATransactionType');

        Definitions::getInstance()->mapSpecificFieldFromValue('TransactionType', 'NotATransactionType');
    }
}
