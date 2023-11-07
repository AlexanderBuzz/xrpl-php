<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XRPL_PHP\Client;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use XRPL_PHP\Models\BaseRequest;
use XRPL_PHP\Models\BaseResponse;
use XRPL_PHP\Models\ErrorResponse;
use XRPL_PHP\Models\Ledger\LedgerRequest;
use XRPL_PHP\Models\Transaction\SubmitResponse;
use XRPL_PHP\Models\Transaction\TransactionTypes\BaseTransaction as Transaction;
use XRPL_PHP\Models\Transaction\TxResponse;
use XRPL_PHP\Wallet\Wallet;
use function XRPL_PHP\Sugar\autofill;
use function XRPL_PHP\Sugar\fundWallet;
use function XRPL_PHP\Sugar\getXrpBalance;
use function XRPL_PHP\Sugar\submit;
use function XRPL_PHP\Sugar\submitAndWait;

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

    private float $timeout;

    public function __construct(
        string $connectionUrl,
        ?float $feeCushion = null,
        ?string $maxFeeXrp = null,
        ?float $timeout = 3.0
    ) {
        $this->connectionUrl = $connectionUrl;

        $this->feeCushion = $feeCushion ?? self::DEFAULT_FEE_CUSHION;

        $this->maxFeeXrp = $maxFeeXrp ?? self::DEFAULT_MAX_FEE_XRP;

        $this->timeout = $timeout;

        $stack = HandlerStack::create(new CurlHandler());

        $this->restClient = new Client(
            [
                'base_uri' => $this->connectionUrl,
                'handler' => $stack,
                'timeout' => $this->timeout,
            ]
        );
    }

    /**
     * Issue a asyncronous JSON RPC request using raw data.
     *
     * @param string $method
     * @param string $resource
     * @param string|null $body
     * @return PromiseInterface
     */
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

    /***
     * Issue a asynchronous JSON RPC request using a method object.
     *
     * @param BaseRequest $request
     * @param bool|null $returnRawResponse
     * @return PromiseInterface
     */
    public function request(BaseRequest $request, ?bool $returnRawResponse = false): PromiseInterface
    {
        $promise = $this->rawRequest(
            'POST',
            '',
            $request->getJson()
        );

        $resolve = function(ResponseInterface $response) use(&$promise, $request, $returnRawResponse): \XRPL_PHP\Models\ErrorResponse|\XRPL_PHP\Models\BaseResponse|\Psr\Http\Message\ResponseInterface {
            if ($returnRawResponse) {
                return $response;
            }

            return $this->handleResponse($request, $response);
        };

        return $promise->then($resolve);
    }

    /**
     * Issue a asyncronous JSON RPC request using raw data.
     *
     * @param string $method
     * @param string $resource
     * @param string|null $body
     * @return ResponseInterface
     * @throws GuzzleException
     */
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

    /**
     * Issue a synchronous JSON RPC request using a method object.
     *
     * @param BaseRequest $request
     * @param bool|null $returnRawResponse
     * @return ResponseInterface|BaseResponse|ErrorResponse
     * @throws GuzzleException
     */
    public function syncRequest(BaseRequest $request, ?bool $returnRawResponse = false): ResponseInterface|BaseResponse|ErrorResponse
    {
        try {
            $response = $this->rawSyncRequest(
                'POST',
                '',
                $request->getJson()
            );
        } catch (RequestException $exception) {
            return $this->handleResponse($request, $exception->getResponse());
        }

        if ($returnRawResponse) {
            return $response;
        }

        return $this->handleResponse($request, $response);
    }

    /**
     * Process RPC response.
     *
     * @param BaseRequest $request
     * @param ResponseInterface|null $response
     * @return BaseResponse|ErrorResponse
     */
    private function handleResponse(BaseRequest $request, ?ResponseInterface $response): BaseResponse|ErrorResponse
    {
        if (is_null($response)) {
            return new ErrorResponse(
                id: null,
                statusCode: 500,
                error: 'RequestException - could not get response',
                errorCode: null,
                errorMessage: null
            );
        }

        $statusCode = $response->getStatusCode();

        if ($statusCode === 200) {
            $rawResponsePayload = $response->getBody()->getContents();
            $responsePayload = json_decode($rawResponsePayload, true);

            if(isset($responsePayload['result']['error'])) {
                return new ErrorResponse(
                    id: null,
                    statusCode: $statusCode,
                    error: $responsePayload['result']['error'],
                    errorCode: $responsePayload['result']['error_code'],
                    errorMessage: $responsePayload['result']['error_message']
                );
            }

            $requestClassName = get_class($request);
            /** @psalm-var class-string  $responseClassName */
            $responseClassName = str_replace('Request', 'Response', $requestClassName);
            /** @var BaseResponse $responseClass  */
            $responseClass = new $responseClassName($responsePayload);

            return $responseClass;
        } else {
            $statusCode = $response->getStatusCode();
            $reason = $response->getReasonPhrase();
            $error = trim($response->getBody()->getContents());

            return new ErrorResponse(null, $statusCode, $error);
        }
    }

    /*
    private function getResponseClass(BaseRequest $request): BaseResponse
    {
        $className = (new \ReflectionClass($request))->getShortName();
        try {
            match ($className) {
                'AccountChannelsRequest' => return new AccountChannelsResponse();
            };
        } catch (\UnhandledMatchError $e) {
            var_dump($e);
        }
    }
    */

    /**
     *
     *
     * @return float
     */
    public function getFeeCushion(): float
    {
        return $this->feeCushion;
    }

    /**
     * Query
     *
     * @return int
     */
    public function getLedgerIndex(): int
    {
        $ledgerRequest = new LedgerRequest(ledgerIndex: 'validated');

        $ledgerResponse = $this->request($ledgerRequest)->wait();

        return $ledgerResponse->getResult()['ledger_index'];
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

    /**
     *
     *
     * @param string $address
     * @return string
     * @throws Exception
     */
    public function getXrpBalance(string $address): string
    {
        return getXrpBalance($this, $address);
    }

    /**
     *
     *
     * @param Wallet|null $wallet
     * @param string|null $faucetHost
     * @return Wallet
     */
    public function fundWallet(?Wallet $wallet = null, ?string $faucetHost = null): Wallet
    {
        return fundWallet($this, $wallet, $faucetHost)['wallet'];
    }

    /**
     *
     *
     * @param Transaction|array $transaction
     * @return array
     */
    public function autofill(Transaction|array &$transaction): array
    {
        return autofill($this, $transaction);
    }

    /**
     *
     *
     * @param Transaction|string|array $transaction
     * @param bool|null $autofill
     * @param bool|null $failHard
     * @param Wallet|null $wallet
     *
     * @return SubmitResponse
     * @throws Exception
     */
    public function submit(
        Transaction|string|array $transaction,
        ?bool                    $autofill = false,
        ?bool                    $failHard = false,
        ?Wallet                  $wallet = null
    ): SubmitResponse
    {
        return submit($this, $transaction, $autofill, $failHard, $wallet);
    }

    /**
     *
     *
     * @param Transaction|string|array $transaction
     * @param bool|null $autofill
     * @param bool|null $failHard
     * @param Wallet|null $wallet
     * @return TxResponse
     * @throws Exception
     */
    public function submitAndWait(
        Transaction|string|array $transaction,
        ?bool                    $autofill = false,
        ?bool                    $failHard = false,
        ?Wallet                  $wallet = null
    ): TxResponse
    {
        return submitAndWait($this, $transaction, $autofill, $failHard, $wallet);
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

        */
}