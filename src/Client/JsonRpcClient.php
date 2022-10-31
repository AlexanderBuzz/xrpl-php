<?php declare(strict_types=1);

namespace XRPL_PHP\Client;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

use Psr\Http\Message\ResponseInterface;
use XRPL_PHP\Core\Utilities;
use XRPL_PHP\Models\BaseRequest;
use XRPL_PHP\Models\Ledger\LedgerRequest;
use XRPL_PHP\Models\Methods\BaseResponse;
use XRPL_PHP\Models\Transactions\Transaction;
use XRPL_PHP\Wallet\Wallet;

use function XRPL_PHP\Sugar\autofill;
use function XRPL_PHP\Sugar\getLedgerIndex;
use function XRPL_PHP\Sugar\getXrpBalance;

class JsonRpcClient
{
    private const DEFAULT_FEE_CUSHION = 1.2;
    private const DEFAULT_MAX_FEE_XRP = '2';

    private const MIN_LIMIT = 10;
    private const MAX_LIMIT = 400;

    private const NORMAL_DISCONNECT_CODE = 1000;

    private Client $restClient;

    private string $connectionUrl;

    private float $feeCushion;

    private string $maxFeeXrp;

    private float $timeout = 3.0;

    public function __construct(
        string $connectionUrl,
        ?float $feeCushion = null,
        ?string $maxFeeXrp  =null
    ) {
        $this->connectionUrl = $connectionUrl;

        $this->feeCushion = $feeCushion ?? self::DEFAULT_FEE_CUSHION;

        $this->maxFeeXrp = $maxFeeXrp ?? self::DEFAULT_MAX_FEE_XRP;

        $stack = HandlerStack::create(new CurlHandler());

        $this->restClient = new Client(
            [
                'base_uri' => $this->connectionUrl,
                'handler' => $stack,
                'timeout' => $this->timeout,
            ]
        );
    }

    public function rawRequest(string $method, string $resource = '', string $body = null): PromiseInterface
    {
        $request = new Request(
            $method,
            $resource,
            ['Content-Type' => 'application/json'],
            $body
        );

        return $this->restClient->sendAsync($request);
    }

    public function request(BaseRequest $request): PromiseInterface
    {
        return $this->rawRequest(
            'POST',
            '',
            $request->getJson()
        );
    }

    public function rawSyncRequest(string $method, string $resource = '', string $body = null): ResponseInterface
    {
        $request = new Request(
            $method,
            $resource,
            ['Content-Type' => 'application/json'],
            $body
        );

        return $this->restClient->send($request);
    }

    public function syncRequest(BaseRequest $request): ResponseInterface
    {
        return $this->rawSyncRequest(
            'POST',
            '',
            $request->getJson()
        );
    }

    public function requestAll(): array
    {
        ///TODO: implement function
    }

    /**
     * @return float
     */
    public function getFeeCushion(): float
    {
        return $this->feeCushion;
    }

    public function getLedgerIndex(): int
    {
        $ledgerRequest = new LedgerRequest(ledgerIndex: 'validated');

        $response = $this->request($ledgerRequest)->wait();
        $json = json_decode($response->getBody());

        return $json['result']['ledger_index'];
    }

    /**
     * @return string
     */
    public function getMaxFeeXrp(): string
    {
        return $this->maxFeeXrp;
    }

    /**
     * @return string
     */
    public function getConnectionUrl(): string
    {
        return $this->connectionUrl;
    }

    private function getCollectKeyFromCommand(string $command): string|null
    {
        return match ($command) {
            'account_channels' => 'channels',
            'account_lines' => 'lines',
            'account_objects' => 'account_objects',
            'account_tx' => 'transactions',
            'account_offers', 'book_offers' => 'offers',
            'ledger_data' => 'state',
            default => null,
        };
    }

    public function getXrpBalance(string $address): string
    {
        return getXrpBalance($this, $address);
    }

    public function fundWallet(Wallet $wallet = null): Wallet
    {
        // Generate a new Wallet if no existing Wallet is provided or its address is invalid to fund
        if ($wallet && Utilities::isValidClassicAddress($wallet->getClassicAddress())) {
            $walletToFund = $wallet;
        } else {
            $walletToFund = Wallet::generate();
        }

        // Create the POST request body
        $body = [
            'destination' => $walletToFund->getClassicAddress()
        ];

        $startingBalance = 0;

        try {
            $this->getXrpBalance($walletToFund->getClassicAddress());
        } catch (Exception $e) {
            // startingBalance remains '0'
        }

        // Options to pass to https.request
    }

    public function autofill(Transaction|array &$tx): array
    {
        return autofill($this, $tx);
    }

    /*
        public function getBalances()
        {
            //TODO: implement function
        }

        public function getOrderBook()
        {
            //TODO: implement function
        }

        public function prepareTransaction()
        {
            //TODO: implement function
        }

        public function submit()
        {
            //TODO: implement function
        }

        public function submitAndWait()
        {
               //TODO: implement function
        }

        */
}