<?php

namespace Hardcastle\XRPL_PHP\Sugar;

use Exception;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use GuzzleHttp\Promise\Promise;
use Hardcastle\XRPL_PHP\Models\Account\AccountInfoRequest;
use Hardcastle\XRPL_PHP\Models\Account\AccountLinesRequest;
use Hardcastle\XRPL_PHP\Models\ErrorResponse;

function formatBalances(array $trustlines): array
{
    /*
    $fn = function (Trustline $trustline) {
        return [

        ];
    };
    return array_map($fn, $trustlines);
    */
}

if (! function_exists('Hardcastle\XRPL_PHP\Sugar\getXrpBalance')) {

    /**
     * @throws Exception
     */
    function getXrpBalance(
        JsonRpcClient $client,
        string $address,
        ?string $ledgerHash = null,
        ?string $ledgerIndex = 'validated',
    ): string
    {
        $accountInfoRequest = new AccountInfoRequest(
            account: $address,
            ledgerHash: $ledgerHash,
            ledgerIndex: $ledgerIndex
        );

        $xrpResponse = $client->request($accountInfoRequest)->wait();

        if($xrpResponse::class === ErrorResponse::class) {
            throw new Exception($xrpResponse->getError());
        }

        return dropsToXrp($xrpResponse->getResult()['account_data']['Balance']);
    }
}

if (! function_exists('Hardcastle\XRPL_PHP\Sugar\getBalances')) {

    /**
     * @param JsonRpcClient $client
     * @param string $address
     * @param string|null $ledgerHash
     * @param string|null $ledgerIndex
     * @param string|null $peer
     * @param int|null $limit
     * @return array
     * @throws Exception
     */
    function getBalances(
        JsonRpcClient $client,
        string $address,
        ?string $ledgerHash = null,
        ?string $ledgerIndex = 'validated',
        ?string $peer = null,
        ?int $limit = null
    ): array
    {
        $balances = [];

        // 1. Get XRP Balance (if no peer filter)
        if (!$peer) {
            try {
                $xrpBalance = getXrpBalance($client, $address, $ledgerHash, $ledgerIndex);
                $balances[] = [
                    'currency' => 'XRP',
                    'value' => $xrpBalance
                ];
            } catch (Exception $e) {
                // If account not found, it might still have trustlines (rare but possible if deleted)
                // or we just ignore and continue to trustlines
            }
        }

        // 2. Get Trustline Balances
        $marker = null;
        while (true) {
            $linesRequest = new AccountLinesRequest(
                account: $address,
                ledgerHash: $ledgerHash,
                ledgerIndex: $ledgerIndex,
                peer: $peer,
                limit: $limit,
                marker: $marker
            );

            $response = $client->request($linesRequest)->wait();

            if ($response::class === ErrorResponse::class) {
                if ($response->getError() === 'actNotFound' && !empty($balances)) {
                    // We already have XRP balance, so we can return it even if actNotFound for lines
                    break;
                }
                throw new Exception($response->getError());
            }

            $result = $response->getResult();
            foreach ($result['lines'] as $line) {
                $balances[] = [
                    'value' => $line['balance'],
                    'currency' => $line['currency'],
                    'issuer' => $line['account']
                ];
            }

            $marker = $result['marker'] ?? null;
            if (!$marker || ($limit && count($balances) >= $limit)) {
                break;
            }
        }

        if ($limit && count($balances) > $limit) {
            return array_slice($balances, 0, $limit);
        }

        return $balances;
    }
}