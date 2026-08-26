<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Client;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Hardcastle\XRPL_PHP\Core\Networks;
use Hardcastle\XRPL_PHP\Models\BaseRequest;
use Hardcastle\XRPL_PHP\Models\BaseResponse;
use Hardcastle\XRPL_PHP\Models\ErrorResponse;
use Hardcastle\XRPL_PHP\Models\Ledger\LedgerRequest;
use Hardcastle\XRPL_PHP\Models\Transaction\SubmitResponse;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions\Definitions;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\BaseTransaction as Transaction;
use Hardcastle\XRPL_PHP\Models\Transaction\TxResponse;
use Hardcastle\XRPL_PHP\Wallet\Wallet;

/**
 * A connection to a rippled server over JSON-RPC.
 *
 * Beyond issuing requests it offers the operations most callers need -
 * autofill, submit, balances, transaction history - each delegating to a class
 * of its own.
 */
class JsonRpcClient
{
    private const DEFAULT_FEE_CUSHION = 1.2;
    private const DEFAULT_MAX_FEE_XRP = '2';

    private const MIN_LIMIT = 10;
    private const MAX_LIMIT = 400;

    private const NORMAL_DISCONNECT_CODE = 1000;

    private readonly Client $restClient;

    private readonly string $connectionUrl;

    private readonly float $feeCushion;

    private readonly string $maxFeeXrp;

    /**
     * Open a connection to a rippled server.
     * The URL may be a short network name such as 'testnet' instead of an address.
     * Pass definitions to talk to a network other than the XRP Ledger.
     */
    public function __construct(
        string $connectionUrl,
        ?float $feeCushion = null,
        ?string $maxFeeXrp = null,
        private readonly float $timeout = 3.0,
        private ?Definitions $definitions = null
    ) {
        $this->connectionUrl = $this->getNetworkUrl($connectionUrl);

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

    /**
     * Issue a asyncronous JSON RPC request using raw data.
     *
     * @param string $method
     * @param string $resource
     * @param string|null $body
     * @return PromiseInterface
     */
    public function rawRequest(string $method, string $resource = '', ?string $body = null): PromiseInterface
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
    /**
     * Issue a request and get a promise for the typed response.
     * Use syncRequest() when the answer is needed right away.
     */
    public function request(BaseRequest $request, ?bool $returnRawResponse = false): PromiseInterface
    {
        $promise = $this->rawRequest(
            'POST',
            '',
            $request->getJson()
        );

        $resolve = function(ResponseInterface $response) use(&$promise, $request, $returnRawResponse): \Hardcastle\XRPL_PHP\Models\ErrorResponse|\Hardcastle\XRPL_PHP\Models\BaseResponse|\Psr\Http\Message\ResponseInterface {
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
    public function rawSyncRequest(string $method, string $resource = '', ?string $body = null): ResponseInterface
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

            $requestClassName = $request::class;
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
    /**
     * The collaborator that fills in Sequence, Fee and LastLedgerSequence.
     *
     * Override this in a subclass to supply a different one. A network whose
     * fee model differs from the XRP Ledger's - Xahau prices per transaction,
     * because hooks may fire - needs its own, and overriding here puts it into
     * every path that autofills, including submitAndWait().
     *
     * @return Autofiller
     */
    public function getAutofiller(): Autofiller
    {
        return new Autofiller($this);
    }

    /**
     * The collaborator that submits transactions and polls for their outcome.
     *
     * @return Submitter
     */
    public function getSubmitter(): Submitter
    {
        return new Submitter($this);
    }

    /**
     * The collaborator that reads balances and transaction history.
     *
     * @return AccountReader
     */
    public function getAccountReader(): AccountReader
    {
        return new AccountReader($this);
    }

    /**
     * The collaborator that reads the order book.
     *
     * @return OrderbookReader
     */
    public function getOrderbookReader(): OrderbookReader
    {
        return new OrderbookReader($this);
    }

    /**
     * The collaborator that looks up the network fee.
     *
     * @return FeeCalculator
     */
    public function getFeeCalculator(): FeeCalculator
    {
        return new FeeCalculator($this);
    }

    /**
     * The collaborator that funds wallets from a faucet.
     *
     * @return Faucet
     */
    public function getFaucet(): Faucet
    {
        return new Faucet($this);
    }

    /**
     * The definitions transactions of this connection are encoded against.
     *
     * Defaults to the bundled XRP Ledger definitions; a client for another
     * network is constructed with that network's set.
     *
     * @return Definitions
     * @throws Exception
     */
    public function getDefinitions(): Definitions
    {
        return $this->definitions ??= Definitions::getInstance();
    }

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
        return $this->getAccountReader()->getXrpBalance($address);
    }
    /**
     * Every balance an account holds: XRP and all its trust lines.
     * Pages through the ledger until there is nothing left, so a large account
     * causes several round trips.
     *
     * @param string $address
     * @param string|null $ledgerHash
     * @param string|null $ledgerIndex
     * @param string|null $peer
     * @param int|null $limit
     * @return array
     * @throws Exception
     */
    public function getBalances(
        string $address,
        ?string $ledgerHash = null,
        ?string $ledgerIndex = 'validated',
        ?string $peer = null,
        ?int $limit = null
    ): array
    {
        return $this->getAccountReader()->getBalances($address, $ledgerHash, $ledgerIndex, $peer, $limit);
    }
    /**
     * The transaction history of an account, newest first unless $forward is set.
     * Pages through the ledger the same way getBalances() does.
     *
     * @param string $address
     * @param int|null $ledgerIndexMin
     * @param int|null $ledgerIndexMax
     * @param string|null $ledgerHash
     * @param string|null $ledgerIndex
     * @param bool|null $binary
     * @param bool|null $forward
     * @param int|null $limit
     * @param mixed|null $marker
     * @return array
     * @throws Exception
     */
    public function getTransactions(
        string $address,
        ?int $ledgerIndexMin = null,
        ?int $ledgerIndexMax = null,
        ?string $ledgerHash = null,
        ?string $ledgerIndex = 'validated',
        ?bool $binary = null,
        ?bool $forward = null,
        ?int $limit = null,
        mixed $marker = null
    ): array
    {
        return $this->getAccountReader()->getTransactions($address, $ledgerIndexMin, $ledgerIndexMax, $ledgerHash, $ledgerIndex, $binary, $forward, $limit, $marker);
    }
    /**
     * The offers standing in one order book of the decentralized exchange.
     *
     * @param array $takerGets
     * @param array $takerPays
     * @param string|null $ledgerHash
     * @param string|null $ledgerIndex
     * @param int|null $limit
     * @param string|null $taker
     * @return array
     * @throws Exception
     */
    public function getOrderbook(
        array $takerGets,
        array $takerPays,
        ?string $ledgerHash = null,
        ?string $ledgerIndex = 'validated',
        ?int $limit = null,
        ?string $taker = null
    ): array
    {
        return $this->getOrderbookReader()->getOrderbook($takerGets, $takerPays, $ledgerHash, $ledgerIndex, $limit, $taker);
    }

    /**
     * @param int|null $cushion
     * @return string
     * @throws \Brick\Math\Exception\MathException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     */
    public function getFeeXrp(?int $cushion = null): string
    {
        return $this->getFeeCalculator()->getFeeXrp($cushion === null ? null : (float)$cushion);
    }
    /**
     * Ask a test network faucet for a funded wallet.
     * Generates one if none is given, and waits until the funds have arrived.
     *
     * @param Wallet|null $wallet
     * @param string|null $faucetHost
     * @return Wallet
     */
    public function fundWallet(?Wallet $wallet = null, ?string $faucetHost = null): Wallet
    {
        return $this->getFaucet()->fundWallet($wallet, $faucetHost)['wallet'];
    }

    /**
     * Fill in Sequence, Fee and LastLedgerSequence where the transaction does
     * not carry them already.
     *
     * The transaction used to be taken by reference although it was never
     * modified, which forced callers to pass a variable. It is passed by value
     * now; existing calls keep working.
     *
     * @param Transaction|array $transaction
     * @param int|null $signersCount Number of signatures a multi-signed transaction will carry
     * @return array
     * @throws Exception
     */
    public function autofill(Transaction|array $transaction, ?int $signersCount = null): array
    {
        return $this->getAutofiller()->autofill($transaction, $signersCount);
    }
    /**
     * Submit a transaction and return the server's preliminary opinion.
     * That opinion is not an outcome; use submitAndWait() when it matters.
     *
     * @param Transaction|string|array $transaction
     * @param bool|null $autofill
     * @param bool|null $failHard
     * @param Wallet|null $wallet
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
        return $this->getSubmitter()->submit($transaction, $autofill, $failHard, $wallet);
    }
    /**
     * Submit a transaction and wait until its outcome is final.
     * Polls until the transaction is in a validated ledger, or until its
     * LastLedgerSequence has passed and it never can be.
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
        return $this->getSubmitter()->submitAndWait($transaction, $autofill, $failHard, $wallet);
    }

    /**
     *
     *
     * @param string $connection
     *
     * @return string
     */
    private function getNetworkUrl(string $connection): string
    {
        try {
            $network = Networks::getNetwork($connection);
            return $network['jsonRpcUrl'];
        } catch (Exception) {
            return $connection;
        }
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