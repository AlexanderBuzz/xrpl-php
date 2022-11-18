<?php

namespace XRPL_PHP\Sugar;

use Exception;
use XRPL_PHP\Client\JsonRpcClient;
use GuzzleHttp\Promise\Promise;
use XRPL_PHP\Models\Account\AccountInfoRequest;
use XRPL_PHP\Models\Account\AccountLinesRequest;


if (! function_exists('XRPL_PHP\Sugar\getXrpBalance')) {

    /**
     * @throws Exception
     */
    function getXrpBalance(
        JsonRpcClient $client,
        string $address,
        ?string $ledgerHash = null,
        ?string $ledgerIndex = null,
    ): string
    {
        //$xrpRequest = new AccountInfoRequest($address, $ledgerIndex, $ledgerIndex || 'validated');
        $xrpRequest = new AccountInfoRequest($address);
        $body = json_encode($xrpRequest->getBody());
        $response = $client->rawSyncRequest('POST', '', $body);

        $content = $response->getBody()->getContents();
        $json = json_decode($content, true);

        return dropsToXrp($json['result']['account_data']['Balance']);
    }
}

if (! function_exists('XRPL_PHP\Sugar\getBalances')) {

    function getBalances(
        JsonRpcClient $client,
        string $address,
        ?string $ledgerHash = null,
        ?string $ledgerIndex = null,
        ?string $peer = null,
        ?int $limit = null
    ): array
    {
        //TODO: Complete this function!

        $balances = [];

        $xrp = '';
        if(!$peer) {
            $xrp = getXrpBalance($client, $address, $ledgerHash, $ledgerIndex);
        }

        $linesRequest = new AccountLinesRequest(
            $address,
            $ledgerHash,
            $ledgerIndex,
            $peer,
            $limit
        );


        return array_slice($balances, 0, $limit);
    }
}