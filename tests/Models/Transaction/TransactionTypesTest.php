<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Models\Transaction;

use PHPUnit\Framework\TestCase;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\Payment;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\AccountSet;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\AccountDelete;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\CheckCancel;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\CheckCash;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\CheckCreate;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\DepositPreauth;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\EscrowCancel;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\EscrowCreate;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\EscrowFinish;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\NFTokenAcceptOffer;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\NFTokenBurn;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\NFTokenCancelOffer;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\NFTokenCreateOffer;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\NFTokenMint;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\OfferCancel;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\OfferCreate;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\PaymentChannelClaim;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\PaymentChannelCreate;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\PaymentChannelFund;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\SetRegularKey;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\SignerListSet;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\TrustSet;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\XChainCreateBridge;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\XChainCommit;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\XChainClaim;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\XChainAccountCreateCommit;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\XChainAddAccountCreateAttestation;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\XChainAddClaimAttestation;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\XChainCreateClaimID;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\XChainModifyBridge;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\DIDSet;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\DIDDelete;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\OracleSet;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\Clawback;
use Hardcastle\XRPL_PHP\Hooks\Models\Transaction\TransactionTypes\TicketCancel;
use Hardcastle\XRPL_PHP\Hooks\Models\Transaction\TransactionTypes\URITokenMint;
use Hardcastle\XRPL_PHP\Hooks\Models\Transaction\TransactionTypes\URITokenBurn;
use Hardcastle\XRPL_PHP\Hooks\Models\Transaction\TransactionTypes\URITokenBuy;
use Hardcastle\XRPL_PHP\Hooks\Models\Transaction\TransactionTypes\URITokenCreateSellOffer;
use Hardcastle\XRPL_PHP\Hooks\Models\Transaction\TransactionTypes\URITokenCancelSellOffer;
use Hardcastle\XRPL_PHP\Hooks\Models\Transaction\TransactionTypes\SetHook;
use Hardcastle\XRPL_PHP\Hooks\Models\Transaction\TransactionTypes\Invoke;
use Hardcastle\XRPL_PHP\Hooks\Models\Transaction\TransactionTypes\GenesisMint;
use Hardcastle\XRPL_PHP\Hooks\Models\Transaction\TransactionTypes\Import;
use Hardcastle\XRPL_PHP\Hooks\Models\Transaction\TransactionTypes\ClaimReward;
use Hardcastle\XRPL_PHP\Hooks\Models\Transaction\TransactionTypes\UNLReport;

final class TransactionTypesTest extends TestCase
{
    private array $commonFields = [
        'Account' => 'rPT1Sjq2YGrBMTttX4GZHjKu9dyfzbpAYe',
        'Fee' => '10',
        'Sequence' => 1,
    ];

    public function testPayment(): void
    {
        $tx = array_merge($this->commonFields, [
            'TransactionType' => 'Payment',
            'Amount' => '1000000',
            'Destination' => 'rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh',
        ]);
        $model = new Payment($tx);
        $this->assertEquals($tx, $model->toArray());
    }

    public function testAccountSet(): void
    {
        $tx = array_merge($this->commonFields, [
            'TransactionType' => 'AccountSet',
            'Domain' => '6578616d706c652e636f6d',
            'SetFlag' => 3,
        ]);
        $model = new AccountSet($tx);
        $this->assertEquals($tx, $model->toArray());
    }

    public function testTrustSet(): void
    {
        $tx = array_merge($this->commonFields, [
            'TransactionType' => 'TrustSet',
            'LimitAmount' => [
                'currency' => 'USD',
                'issuer' => 'rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh',
                'value' => '100'
            ],
        ]);
        $model = new TrustSet($tx);
        $this->assertEquals($tx, $model->toArray());
    }

    public function testOfferCreate(): void
    {
        $tx = array_merge($this->commonFields, [
            'TransactionType' => 'OfferCreate',
            'TakerGets' => '1000000',
            'TakerPays' => [
                'currency' => 'USD',
                'issuer' => 'rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh',
                'value' => '100'
            ],
        ]);
        $model = new OfferCreate($tx);
        $this->assertEquals($tx, $model->toArray());
    }

    public function testXChainCreateBridge(): void
    {
        $tx = array_merge($this->commonFields, [
            'TransactionType' => 'XChainCreateBridge',
            'XChainBridge' => [
                'LockingChainDoor' => 'rMAXHh7Zf9VAC6P8atEksFrFX9oHTo4p6E',
                'LockingChainIssue' => ['currency' => 'XRP'],
                'IssuingChainDoor' => 'rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh',
                'IssuingChainIssue' => ['currency' => 'XRP'],
            ],
            'SignatureReward' => '100',
            'MinAccountCreateAmount' => '1000',
        ]);
        $model = new XChainCreateBridge($tx);
        $this->assertEquals($tx, $model->toArray());
    }

    public function testDIDSet(): void
    {
        $tx = array_merge($this->commonFields, [
            'TransactionType' => 'DIDSet',
            'Data' => '64617461',
            'DIDDocument' => '646f63',
            'URI' => '757269',
        ]);
        $model = new DIDSet($tx);
        $this->assertEquals($tx, $model->toArray());
    }

    public function testOracleSet(): void
    {
        $tx = array_merge($this->commonFields, [
            'TransactionType' => 'OracleSet',
            'OracleDocumentID' => 1,
            'LastUpdateTime' => 12345678,
            'PriceDataSeries' => [
                [
                    'PriceData' => [
                        'BaseAsset' => 'XRP',
                        'QuoteAsset' => 'USD',
                        'AssetPrice' => 500000,
                    ]
                ]
            ],
            'AssetClass' => '63757272656e6379',
        ]);
        $model = new OracleSet($tx);
        $this->assertEquals($tx, $model->toArray());
    }

    public function testClawback(): void
    {
        $tx = array_merge($this->commonFields, [
            'TransactionType' => 'Clawback',
            'Amount' => [
                'currency' => 'USD',
                'issuer' => 'rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh',
                'value' => '100'
            ],
        ]);
        $model = new Clawback($tx);
        $this->assertEquals($tx, $model->toArray());
    }

    public function testTicketCancel(): void
    {
        $tx = array_merge($this->commonFields, [
            'TransactionType' => 'TicketCancel',
            'TicketSequence' => 123,
        ]);
        $model = new TicketCancel($tx);
        $this->assertEquals($tx, $model->toArray());
    }

    public function testURITokenMint(): void
    {
        $tx = array_merge($this->commonFields, [
            'TransactionType' => 'URITokenMint',
            'URI' => '687474703a2f2f6578616d706c652e636f6d',
            'Destination' => 'rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh',
        ]);
        $model = new URITokenMint($tx);
        $this->assertEquals($tx, $model->toArray());
    }

    public function testURITokenBurn(): void
    {
        $tx = array_merge($this->commonFields, [
            'TransactionType' => 'URITokenBurn',
            'URITokenID' => '0000000000000000000000000000000000000000000000000000000000000000',
        ]);
        $model = new URITokenBurn($tx);
        $this->assertEquals($tx, $model->toArray());
    }

    public function testURITokenBuy(): void
    {
        $tx = array_merge($this->commonFields, [
            'TransactionType' => 'URITokenBuy',
            'URITokenID' => '0000000000000000000000000000000000000000000000000000000000000000',
            'Amount' => '1000000',
        ]);
        $model = new URITokenBuy($tx);
        $this->assertEquals($tx, $model->toArray());
    }

    public function testURITokenCreateSellOffer(): void
    {
        $tx = array_merge($this->commonFields, [
            'TransactionType' => 'URITokenCreateSellOffer',
            'URITokenID' => '0000000000000000000000000000000000000000000000000000000000000000',
            'Amount' => '1000000',
            'Destination' => 'rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh',
        ]);
        $model = new URITokenCreateSellOffer($tx);
        $this->assertEquals($tx, $model->toArray());
    }

    public function testURITokenCancelSellOffer(): void
    {
        $tx = array_merge($this->commonFields, [
            'TransactionType' => 'URITokenCancelSellOffer',
            'URITokenID' => '0000000000000000000000000000000000000000000000000000000000000000',
        ]);
        $model = new URITokenCancelSellOffer($tx);
        $this->assertEquals($tx, $model->toArray());
    }

    public function testSetHook(): void
    {
        $tx = array_merge($this->commonFields, [
            'TransactionType' => 'SetHook',
            'Hooks' => [
                [
                    'Hook' => [
                        'HookHash' => '0000000000000000000000000000000000000000000000000000000000000000'
                    ]
                ]
            ],
        ]);
        $model = new SetHook($tx);
        $this->assertEquals($tx, $model->toArray());
    }

    public function testInvoke(): void
    {
        $tx = array_merge($this->commonFields, [
            'TransactionType' => 'Invoke',
            'Destination' => 'rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh',
            'Blob' => '64617461',
        ]);
        $model = new Invoke($tx);
        $this->assertEquals($tx, $model->toArray());
    }

    public function testGenesisMint(): void
    {
        $tx = array_merge($this->commonFields, [
            'TransactionType' => 'GenesisMint',
            'Amount' => '1000000',
            'Destination' => 'rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh',
        ]);
        $model = new GenesisMint($tx);
        $this->assertEquals($tx, $model->toArray());
    }

    public function testImport(): void
    {
        $tx = array_merge($this->commonFields, [
            'TransactionType' => 'Import',
            'Blob' => '64617461',
        ]);
        $model = new Import($tx);
        $this->assertEquals($tx, $model->toArray());
    }

    public function testClaimReward(): void
    {
        $tx = array_merge($this->commonFields, [
            'TransactionType' => 'ClaimReward',
            'Issuer' => 'rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh',
        ]);
        $model = new ClaimReward($tx);
        $this->assertEquals($tx, $model->toArray());
    }

    public function testUNLReport(): void
    {
        $tx = array_merge($this->commonFields, [
            'TransactionType' => 'UNLReport',
            'ActiveAccounts' => [
                [
                    'ActiveAccount' => [
                        'Account' => 'rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh',
                    ]
                ]
            ],
        ]);
        $model = new UNLReport($tx);
        $this->assertEquals($tx, $model->toArray());
    }

    public function testUNLReportWithInt64(): void
    {
        $this->markTestIncomplete('The Issue #36 blob uses Int64 RewardAccumulator (11:9) which is not in official Xahau definitions.');
    }
}
