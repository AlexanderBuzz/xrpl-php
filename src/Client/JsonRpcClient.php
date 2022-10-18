<?php declare(strict_types=1);

namespace XRPL_PHP\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

use Psr\Http\Message\ResponseInterface;
use XRPL_PHP\Core\Utilities;
use XRPL_PHP\Models\BaseRequest;
use XRPL_PHP\Models\Methods\BaseResponse;
use XRPL_PHP\Models\Transactions\Transaction;
use XRPL_PHP\Wallet\Wallet;

use function XRPL_PHP\Sugar\autofill;
use function XRPL_PHP\Sugar\getXrpBalance;

class JsonRpcClient
{
    private Client $restClient;

    private string $connectionUrl;

    private float $timeout = 3.0;

    public function __construct(string $connectionUrl)
    {
        $this->connectionUrl = $connectionUrl;

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

        if ($wallet && Utilities::isValidClassicAddress($wallet->getClassicAddress())) {
            $walletToFund = $wallet;
        } else {
            $walletToFund = Wallet::generate();
        }

        $body = [
            'destination' => $walletToFund->getClassicAddress()
        ];

        $startingBalance = 0;
    }

    public function autofill(Transaction $transaction): PromiseInterface
    {
        return autofill($this, $transaction);
    }

    /*
        public function getBalances()
        {
            //TODO: implement function
        }

        public function getLedgerIndex()
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