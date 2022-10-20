<?php

namespace XRPL_PHP\Sugar;

use BI\BigInteger;
use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use XRPL_PHP\Client\JsonRpcClient;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\Utils as PromiseUtilities;
use XRPL_PHP\Core\Utilities;
use XRPL_PHP\Models\Account\AccountInfoRequest;
use XRPL_PHP\Models\Account\AccountLinesRequest;
use XRPL_PHP\Models\Account\AccountObjectsRequest;
use XRPL_PHP\Models\Methods\ServerStateRequest;
use XRPL_PHP\Models\Transactions\Transaction;

use function XRPL_PHP\Sugar\getFeeXrp;
use function XRPL_PHP\Sugar\xrpToDrops;

function setValidAddresses (array &$tx): void
{
    validateAccountAddress($tx, 'Account', 'SourceTag');

    if (isset($tx['Destination'])) {
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
function validateAccountAddress (array &$tx, string $accountField, string $tagField): void
{
    ['classicAccount' => $classicAccount, 'tag' => $tag] = getClassicAccountAndTag($tx[$accountField]);

    $tx[$accountField] = $classicAccount;

    if (isset($tag) && $tag !== false) {
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
    $account = $tx[$fieldName] ?? null;

    if(is_string($account)) {
        ['classicAccount' => $classicAccount] = getClassicAccountAndTag($account);
        $tx[$fieldName] = $classicAccount;
    }
}

function setNextValidSequenceNumber (JsonRpcClient $client, array &$tx): string
{
    $accountInfoRequest = new AccountInfoRequest(
            account: $tx['Account'],
            ledgerIndex: 'current'
    );

    $response = $client->request($accountInfoRequest)->wait();
    $json = json_decode($response->getBody());

    $tx['Sequence'] = $json['result']['account_data']['Sequence'];
}

function fetchAccountDeleteFee (JsonRpcClient $client): BigDecimal
{
    $serverStateRequest = new ServerStateRequest();

    $response = $client->request($serverStateRequest)->wait();
    $json = json_decode($response->getBody());

    $fee = $json['result']['state']['validated_ledger']['reserve_inc'] ?? null;

    if (is_null($fee)) {
        throw new Exception('Address includes a tag that does not match the tag specified in the transaction');
    }

    return BigDecimal::of($fee);
}

function calculateFeePerTransactionType (JsonRpcClient $client, array &$tx, int $signersCount = 0): void
{
    $netFeeXrp = getFeeXrp($client);
    $netFeeDrops = xrpToDrops($netFeeXrp);
    $baseFee = BigDecimal::of($netFeeDrops);

    if ($tx['TransactionType'] === 'EscrowFinish' && !is_null($tx['Fulfillment'])) {
        // 10 drops × (33 + (Fulfillment size in bytes / 16))
        $fulfillmentBytesSize = ceil(strlen($tx['Fulfillment'] / 2));
        $product = BigDecimal::of(scaleValue($netFeeDrops, 33 + $fulfillmentBytesSize / 16));
        $baseFee = $product->toScale(0, RoundingMode::CEILING);
    }

    if ($tx['TransactionType'] === 'AccountDelete') {
        $baseFee = fetchAccountDeleteFee($client);
    }

    /*
   * Multi-signed Transaction
   * 10 drops × (1 + Number of Signatures Provided)
   */
    if ($signersCount > 0) {
        $baseFee = BigDecimal::sum($baseFee, scaleValue($netFeeDrops, 1 + $signersCount));
    }

    $maxFeeDrops = xrpToDrops($client->getMaxFeeXrp());
    $totalFee = ($tx['TransactionType'] === 'AccountDelete') ? $baseFee : BigDecimal::min($baseFee, $maxFeeDrops);

    // Round up baseFee and return it as a string
    $tx['Fee'] = $totalFee->toScale(0, RoundingMode::CEILING)->toBase(10);
}

function scaleValue ($value, $multiplier): string
{
    return BigDecimal::of($value)->multipliedBy($multiplier);
}

function setLatestValidatedLedgerSequence (JsonRpcClient $client, array &$tx): void
{
    $ledgerSequence = $client->getLedgerIndex();
    $ledgerOffset = 20;
    $tx['LastLedgerSequence'] = $ledgerSequence + $ledgerOffset;
}

function checkAccountDeleteBlockers (JsonRpcClient $client, array $tx): void
{
    $request = new AccountObjectsRequest(
        account: $tx['Account'],
        ledgerIndex: 'validated',
        deletionBlockersOnly: true
    );

    $response = $client->request($request)->wait();

    $json = json_decode($response->getBody());

    if ($json['result']['account_objects']['length'] > 0) {
        throw new Exception("Account {$tx['Account']} cannot be deleted; there are Escrows, PayChannels, RippleStates, or Checks associated with the account.");
    }
}

if (! function_exists('XRPL_PHP\Sugar\autofill')) {

    function autofill(
        JsonRpcClient $client,
        array $tx,
        ?int $signersCount = null
    ): array
    {
        setValidAddresses($tx);

        //TODO: check function
        //setTransactionFlagsToNumber($tx);

        $promises = [];

        //TODO: check call by reference

        if ($tx['Sequence'] === null) {
            $promises[] = new Promise(function() use ($client, &$tx) {
                setLatestValidatedLedgerSequence($client, $tx);
            });
        }

        if ($tx['Fee'] === null) {
            $promises[] =  new Promise(function() use ($client, &$tx, $signersCount) {
                calculateFeePerTransactionType($client, $tx, $signersCount);
            });
        }

        if ($tx['LastLedgerSequence'] === null) {
            $promises[] = new Promise(function() use ($client, &$tx) {
                setLatestValidatedLedgerSequence($client, $tx);
            });
        }

        if ($tx['TransactionType'] === 'AccountDelete') {
            $promises[] = new Promise(function() use ($client, &$tx) {
                checkAccountDeleteBlockers($client, $tx);
            });
        }

        return PromiseUtilities::all($promises)->then(fn() => $tx)->wait();
    }
}