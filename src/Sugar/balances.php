<?php

namespace XRPL_PHP\Sugar;

use Exception;
use XRPL_PHP\Client\JsonRpcClient;
use GuzzleHttp\Promise\Promise;
use XRPL_PHP\Models\Account\AccountInfoRequest;
use XRPL_PHP\Models\Account\AccountLinesRequest;
use XRPL_PHP\Models\ErrorResponse;

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

if (! function_exists('XRPL_PHP\Sugar\getXrpBalance')) {

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

        if(get_class($xrpResponse) === ErrorResponse::class) {
            throw new Exception($xrpResponse->getError());
        }

        return dropsToXrp($xrpResponse->getResult()['account_data']['Balance']);
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