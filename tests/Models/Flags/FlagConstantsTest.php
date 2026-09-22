<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Models\Flags;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions\Definitions;
use Hardcastle\XRPL_PHP\Models\Ledger\Flags\AccountRootFlags;
use Hardcastle\XRPL_PHP\Models\Ledger\Flags\CredentialFlags;
use Hardcastle\XRPL_PHP\Models\Ledger\Flags\DirectoryNodeFlags;
use Hardcastle\XRPL_PHP\Models\Ledger\Flags\LoanFlags;
use Hardcastle\XRPL_PHP\Models\Ledger\Flags\MPTokenFlags;
use Hardcastle\XRPL_PHP\Models\Ledger\Flags\MPTokenIssuanceFlags;
use Hardcastle\XRPL_PHP\Models\Ledger\Flags\NFTokenOfferFlags;
use Hardcastle\XRPL_PHP\Models\Ledger\Flags\OfferFlags;
use Hardcastle\XRPL_PHP\Models\Ledger\Flags\RippleStateFlags;
use Hardcastle\XRPL_PHP\Models\Ledger\Flags\SignerListFlags;
use Hardcastle\XRPL_PHP\Models\Ledger\Flags\SponsorshipFlags;
use Hardcastle\XRPL_PHP\Models\Ledger\Flags\VaultFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\AMMClawbackFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\AMMDepositFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\AMMWithdrawFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\AccountSetAsfFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\AccountSetFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\BatchFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\EnableAmendmentFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\GlobalFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\LoanManageFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\LoanPayFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\LoanSetFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\MPTokenAuthorizeFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\MPTokenIssuanceCreateFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\MPTokenIssuanceSetFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\NFTokenCreateOfferFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\NFTokenMintFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\OfferCreateFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\PaymentChannelClaimFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\PaymentFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\SponsorshipSetFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\SponsorshipTransferFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\TrustSetFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\VaultCreateFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\XChainModifyBridgeFlags;

/**
 * The flag constants have to say what rippled says.
 *
 * definitions.json carries the flag tables of rippled 3.3.0 (TRANSACTION_FLAGS,
 * ACCOUNT_SET_FLAGS, LEDGER_ENTRY_FLAGS, verified against TxFlags.h and
 * LedgerFormats.h). Every table has a constants class, and every class has
 * exactly the table's names and values - so a table that gains a flag fails
 * here until the class follows, and a mistyped constant fails against the
 * table.
 */
final class FlagConstantsTest extends TestCase
{
    private const ACCOUNT = 'rPT1Sjq2YGrBMTttX4GZHjKu9dyfzbpAYe';

    private const OTHER_ACCOUNT = 'rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh';

    /**
     * TRANSACTION_FLAGS key => class.
     */
    private const TRANSACTION_CLASSES = [
            'AMMClawback' => AMMClawbackFlags::class,
            'AMMDeposit' => AMMDepositFlags::class,
            'AMMWithdraw' => AMMWithdrawFlags::class,
            'AccountSet' => AccountSetFlags::class,
            'Batch' => BatchFlags::class,
            'EnableAmendment' => EnableAmendmentFlags::class,
            'LoanManage' => LoanManageFlags::class,
            'LoanPay' => LoanPayFlags::class,
            'LoanSet' => LoanSetFlags::class,
            'MPTokenAuthorize' => MPTokenAuthorizeFlags::class,
            'MPTokenIssuanceCreate' => MPTokenIssuanceCreateFlags::class,
            'MPTokenIssuanceSet' => MPTokenIssuanceSetFlags::class,
            'NFTokenCreateOffer' => NFTokenCreateOfferFlags::class,
            'NFTokenMint' => NFTokenMintFlags::class,
            'OfferCreate' => OfferCreateFlags::class,
            'Payment' => PaymentFlags::class,
            'PaymentChannelClaim' => PaymentChannelClaimFlags::class,
            'SponsorshipSet' => SponsorshipSetFlags::class,
            'SponsorshipTransfer' => SponsorshipTransferFlags::class,
            'TrustSet' => TrustSetFlags::class,
            'VaultCreate' => VaultCreateFlags::class,
            'XChainModifyBridge' => XChainModifyBridgeFlags::class,
            'universal' => GlobalFlags::class,
    ];

    /**
     * LEDGER_ENTRY_FLAGS key => class.
     */
    private const LEDGER_CLASSES = [
            'AccountRoot' => AccountRootFlags::class,
            'Credential' => CredentialFlags::class,
            'DirNode' => DirectoryNodeFlags::class,
            'Loan' => LoanFlags::class,
            'MPToken' => MPTokenFlags::class,
            'MPTokenIssuance' => MPTokenIssuanceFlags::class,
            'NFTokenOffer' => NFTokenOfferFlags::class,
            'Offer' => OfferFlags::class,
            'RippleState' => RippleStateFlags::class,
            'SignerList' => SignerListFlags::class,
            'Sponsorship' => SponsorshipFlags::class,
            'Vault' => VaultFlags::class,
    ];

    private static function bundled(): array
    {
        $path = __DIR__ . '/../../../src/Core/RippleBinaryCodec/Definitions/definitions.json';

        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    public static function transactionTableProvider(): array
    {
        $cases = [];
        foreach (self::bundled()['TRANSACTION_FLAGS'] as $type => $flags) {
            $cases[$type] = [$type, $flags];
        }

        return $cases;
    }

    public static function ledgerTableProvider(): array
    {
        $cases = [];
        foreach (self::bundled()['LEDGER_ENTRY_FLAGS'] as $type => $flags) {
            $cases[$type] = [$type, $flags];
        }

        return $cases;
    }

    #[DataProvider('transactionTableProvider')]
    public function testEveryTransactionTableHasItsClass(string $type, array $flags): void
    {
        $this->assertArrayHasKey($type, self::TRANSACTION_CLASSES, "no constants class for the {$type} flags");

        $class = self::TRANSACTION_CLASSES[$type];
        $this->assertSame(self::sorted($flags), self::sorted($class::all()), $class);
        $this->assertSame(self::sorted($flags), self::sorted(Definitions::getInstance()->getTransactionFlags($type)));
    }

    #[DataProvider('ledgerTableProvider')]
    public function testEveryLedgerTableHasItsClass(string $type, array $flags): void
    {
        $this->assertArrayHasKey($type, self::LEDGER_CLASSES, "no constants class for the {$type} flags");

        $class = self::LEDGER_CLASSES[$type];
        $this->assertSame(self::sorted($flags), self::sorted($class::all()), $class);
        $this->assertSame(self::sorted($flags), self::sorted(Definitions::getInstance()->getLedgerEntryFlags($type)));
    }

    public function testAccountSetAsfFlags(): void
    {
        $flags = self::bundled()['ACCOUNT_SET_FLAGS'];

        $this->assertSame(self::sorted($flags), self::sorted(AccountSetAsfFlags::all()));
        $this->assertSame(self::sorted($flags), self::sorted(Definitions::getInstance()->getAccountSetFlags()));
    }

    /**
     * Every class has a table; a class without one would drift unnoticed.
     */
    public function testEveryClassHasItsTable(): void
    {
        $raw = self::bundled();

        $this->assertSame([], array_diff_key(self::TRANSACTION_CLASSES, $raw['TRANSACTION_FLAGS']));
        $this->assertSame([], array_diff_key(self::LEDGER_CLASSES, $raw['LEDGER_ENTRY_FLAGS']));
    }

    /**
     * The well known values, spelled out so that a wholesale mix-up of the
     * tables (say tf and lsf swapped) cannot pass the table comparison.
     */
    public function testWellKnownValues(): void
    {
        $this->assertSame(0x80000000, GlobalFlags::tfFullyCanonicalSig);
        $this->assertSame(0x40000000, GlobalFlags::tfInnerBatchTxn);
        $this->assertSame(0x00020000, PaymentFlags::tfPartialPayment);
        $this->assertSame(0x00080000, OfferCreateFlags::tfSell);
        $this->assertSame(0x00010000, TrustSetFlags::tfSetfAuth);
        $this->assertSame(0x00010000, BatchFlags::tfAllOrNothing);
        $this->assertSame(0x00080000, BatchFlags::tfIndependent);
        $this->assertSame(1, AccountSetAsfFlags::asfRequireDest);
        $this->assertSame(16, AccountSetAsfFlags::asfAllowTrustLineClawback);
        $this->assertSame(0x00020000, AccountRootFlags::lsfRequireDestTag);
        $this->assertSame(0x00400000, AccountRootFlags::lsfGlobalFreeze);
    }

    public function testHasAndParse(): void
    {
        $flags = AccountRootFlags::lsfRequireDestTag | AccountRootFlags::lsfGlobalFreeze | 0x00000001;

        $this->assertTrue(AccountRootFlags::has($flags, AccountRootFlags::lsfRequireDestTag));
        $this->assertFalse(AccountRootFlags::has($flags, AccountRootFlags::lsfDepositAuth));
        $this->assertSame(['lsfRequireDestTag', 'lsfGlobalFreeze'], AccountRootFlags::parse($flags));
        $this->assertSame([], AccountRootFlags::parse(0));
    }

    /**
     * The definitions a network package injects may carry no flag tables.
     */
    public function testDefinitionsWithoutFlagTables(): void
    {
        $raw = self::bundled();
        unset($raw['TRANSACTION_FLAGS'], $raw['ACCOUNT_SET_FLAGS'], $raw['LEDGER_ENTRY_FLAGS']);
        $definitions = Definitions::fromArray($raw);

        $this->assertSame([], $definitions->getTransactionFlags('Payment'));
        $this->assertSame([], $definitions->getAccountSetFlags());
        $this->assertSame([], $definitions->getLedgerEntryFlags('AccountRoot'));
    }

    /**
     * A flag combination survives the codec, and the parsed names come back.
     */
    public function testFlagsRoundtrip(): void
    {
        $codec = new BinaryCodec();
        $tx = [
            'TransactionType' => 'Payment',
            'Account' => self::ACCOUNT,
            'Destination' => self::OTHER_ACCOUNT,
            'Amount' => '1000',
            'Fee' => '12',
            'Sequence' => 1,
            'SigningPubKey' => '',
            'Flags' => GlobalFlags::tfFullyCanonicalSig | PaymentFlags::tfPartialPayment,
        ];

        $decoded = $codec->decode($codec->encode(json_encode($tx, JSON_THROW_ON_ERROR)));

        $this->assertSame($tx['Flags'], $decoded['Flags']);
        $this->assertSame(['tfPartialPayment'], PaymentFlags::parse($decoded['Flags']));
        $this->assertSame(['tfInnerBatchTxn'], GlobalFlags::parse(GlobalFlags::tfInnerBatchTxn));
    }

    private static function sorted(array $flags): array
    {
        ksort($flags);

        return $flags;
    }
}
