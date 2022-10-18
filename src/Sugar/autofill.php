<?php

namespace XRPL_PHP\Sugar;

use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use XRPL_PHP\Client\JsonRpcClient;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\Utils as PromiseUtilities;
use XRPL_PHP\Core\Utilities;
use XRPL_PHP\Models\Account\AccountInfoRequest;
use XRPL_PHP\Models\Account\AccountLinesRequest;
use XRPL_PHP\Models\Account\AccountObjectsRequest;
use XRPL_PHP\Models\Transactions\Transaction;

function setValidAddresses (array &$tx): array
{
    validateAccountAddress($tx, 'Account', 'SourceTag');

    if (!is_null($tx['Destination'])) {
        validateAccountAddress($tx, 'Destination', 'DestinationTag');
    }

    // DepositPreauth:
    convertToClassicAddress($tx, 'Authorize');
    convertToClassicAddress($tx, 'Unauthorize');

    // EscrowCancel, EscrowFinish:
    convertToClassicAddress($tx, 'Owner');

    // SetRegularKey:
    convertToClassicAddress($tx, 'RegularKey');
}

/**
 * @throws Exception
 */
function validateAccountAddress (array &$tx, string $accountField, string $tagField): array
{
    list($classicAccount, $tag) = getClassicAccountAndTag($tx[$accountField]);

    $tx[$accountField] = $classicAccount;

    if (!is_null($tag) && $tag !== false) {
        if(isset($tx[$tagField]) && $tx[$tagField] !== $tag) {
            throw new Exception("The {$tagField}, if present, must match the tag of the {$accountField} X-address");
        }
    }

    $tx[$tagField] = $tag;
}

/**
 * @throws Exception
 */
function getClassicAccountAndTag (string $account, ?int $expectedTag = null): array
{
    if (Utilities::isValidXAddress($account)) {
        $classicAddress = Utilities::xAddressToClassicAddress($account);
        if (!is_null($expectedTag) && $expectedTag !== $classicAddress['tag']) {
            throw new Exception('Address includes a tag that does not match the tag specified in the transaction');
        }

        return [
            'classicAccount' => $classicAddress['classicAddress'],
            'tag' => $classicAddress['tag']
        ];
    }

    return [
        'classicAccount' => $account,
        'tag' => $expectedTag
    ];
}

function convertToClassicAddress (array &$tx, string $fieldName): void
{

}

function setNextValidSequenceNumber (JsonRpcClient $client, array &$tx): array
{

}

function fetchAccountDeleteFee (JsonRpcClient $client): array
{

}

function calculateFeePerTransactionType (JsonRpcClient $client, array &$tx, int $signersCount = 0): array
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
    ): PromiseInterface
    {
        $tx = $transaction->toArray();

        setValidAddresses($tx);

        //setTransactionFlagsToNumber($tx);

        $promises = [];

        if ($tx['Sequence'] === null) {
            $promises[] = setLatestValidatedLedgerSequence($client, $tx);
        }

        if ($tx['Fee'] === null) {
            $promises[] = calculateFeePerTransactionType($client, $tx, $signersCount);
        }

        if ($tx['LastLedgerSequence'] === null) {
            $promises[] = setLatestValidatedLedgerSequence($client, $tx);
        }

        if ($tx['TransactionType'] === 'AccountDelete') {
            $promises[] = checkAccountDeleteBlockers($client, $tx);
        }

        return PromiseUtilities::all($promises);
    }
}