<?php

namespace Hardcastle\XRPL_PHP\Sugar;

use Exception;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use GuzzleHttp\Promise\Promise;
use Hardcastle\XRPL_PHP\Models\Account\AccountInfoRequest;
use Hardcastle\XRPL_PHP\Models\Account\AccountLinesRequest;


use Hardcastle\XRPL_PHP\Models\Account\AccountTxRequest;
use Hardcastle\XRPL_PHP\Models\ErrorResponse;

if (! function_exists('Hardcastle\XRPL_PHP\Sugar\getTransactions')) {

    /**
     * @param JsonRpcClient $client
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
    function getTransactions(
        JsonRpcClient $client,
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
        $transactions = [];

        while (true) {
            $request = new AccountTxRequest(
                account: $address,
                ledgerIndexMin: $ledgerIndexMin,
                ledgerIndexMax: $ledgerIndexMax,
                ledgerHash: $ledgerHash,
                ledgerIndex: $ledgerIndex,
                binary: $binary,
                forward: $forward,
                limit: $limit,
                marker: $marker
            );

            $response = $client->request($request)->wait();

            if ($response::class === ErrorResponse::class) {
                throw new Exception($response->getError());
            }

            $result = $response->getResult();
            $transactions = array_merge($transactions, $result['transactions']);

            $marker = $result['marker'] ?? null;
            if (!$marker || ($limit && count($transactions) >= $limit)) {
                break;
            }
        }

        if ($limit && count($transactions) > $limit) {
            return array_slice($transactions, 0, $limit);
        }

        return $transactions;
    }
}