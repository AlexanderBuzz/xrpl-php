<?php

namespace XRPL_PHP\Sugar;

use XRPL_PHP\Client\JsonRpcClient;
use GuzzleHttp\Promise\Promise;
use XRPL_PHP\Models\Account\AccountInfoRequest;
use XRPL_PHP\Models\Account\AccountLinesRequest;
use XRPL_PHP\Models\Account\AccountObjectsRequest;

function setValidAddresses (array $tx): array
{

}

function validateAccountAddress (array $tx): array
{

}

function getClassicAccountAndTag (string $account, ?int $expectedTag = null): array
{

}

function convertToClassicAddress (array $tx, string $fieldName): void
{

}

function setNextValidSequenceNumber (JsonRpcClient $client, array $tx): array
{

}

function fetchAccountDeleteFee (JsonRpcClient $client): array
{

}

function calculateFeePerTransactionType (JsonRpcClient $client, array $tx, int $signersCount = 0): array
{

}

function scaleValue (): string
{

}

function setLatestValidatedLedgerSequence (JsonRpcClient $client, array $tx): array
{

}

function checkAccountDeleteBlockers (JsonRpcClient $client, array $tx): array
{
    $request = new AccountObjectsRequest(
        account: $tx['Account'],
        ledgerIndex: 'validated',
        deletionBlockersOnly: true
    );

    $response = $client->syncRequest($request);

    $json = json_decode($response->getBody());

    if ($json['result']['account_objects.length'] > 0) {

    }
}

if (! function_exists('XRPL_PHP\Sugar\autofill')) {

    function autofill(
        JsonRpcClient $client,
        Transaction $transaction,
        ?int $signersCount = null
    ): array
    {

    }
}