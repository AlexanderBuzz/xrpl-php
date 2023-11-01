<?php

namespace XRPL_PHP\Sugar;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Exception;
use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Core\CoreUtilities;
use XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use XRPL_PHP\Models\Account\AccountInfoRequest;
use XRPL_PHP\Models\Account\AccountObjectsRequest;
use XRPL_PHP\Models\ErrorResponse;
use XRPL_PHP\Models\ServerInfo\ServerStateRequest;
use XRPL_PHP\Models\Transaction\TransactionTypes\BaseTransaction as Transaction;

/**
 * @param array $tx
 * @return void
 * @throws Exception
 */
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
 * Covert tx if X-address is given. If tag is provided in tx and X-address, check if the match.
 *
 * @param array $tx
 * @param string $accountField
 * @param string $tagField
 * @return void
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

        $tx[$tagField] = $tag;
    }
}

/**
 * @param string $account
 * @param int|null $expectedTag
 * @return array
 * @throws Exception
 */
function getClassicAccountAndTag (string $account, ?int $expectedTag = null): array
{
    if (CoreUtilities::isValidXAddress($account)) {
        $classicAddress = CoreUtilities::xAddressToClassicAddress($account);
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

/**
 * @param array $tx
 * @param string $fieldName
 * @return void
 * @throws Exception
 */
function convertToClassicAddress (array &$tx, string $fieldName): void
{
    $account = $tx[$fieldName] ?? null;

    if(is_string($account)) {
        ['classicAccount' => $classicAccount] = getClassicAccountAndTag($account);
        $tx[$fieldName] = $classicAccount;
    }
}

/**
 * @param JsonRpcClient $client
 * @param array $tx
 * @return void
 * @throws Exception
 */
function setNextValidSequenceNumber (JsonRpcClient $client, array &$tx): void
{
    $accountInfoRequest = new AccountInfoRequest(
        account: $tx['Account'],
        ledgerIndex: 'current'
    );

    $accountInfoResponse = $client->syncRequest($accountInfoRequest);
    if($accountInfoResponse instanceof ErrorResponse) {
        throw new Exception($accountInfoResponse->getError());
    }

    $tx['Sequence'] = $accountInfoResponse->getResult()['account_data']['Sequence'];
}

/**
 * @param JsonRpcClient $client
 * @return BigDecimal
 * @throws MathException
 */
function fetchAccountDeleteFee (JsonRpcClient $client): BigDecimal
{
    $serverStateRequest = new ServerStateRequest();

    $serverStateResponse = $client->request($serverStateRequest)->wait();

    $fee = $serverStateResponse->getResult()['state']['validated_ledger']['reserve_inc'] ?? null;

    if (is_null($fee)) {
        throw new Exception('Address includes a tag that does not match the tag specified in the transaction');
    }

    return BigDecimal::of($fee);
}

/**
 * @param JsonRpcClient $client
 * @param array $tx
 * @param int|null $signersCount
 * @return void
 * @throws MathException
 * @throws RoundingNecessaryException
 */
function calculateFeePerTransactionType (JsonRpcClient $client, array &$tx, ?int $signersCount = 0): void
{
    $netFeeXrp = getFeeXrp($client);
    $netFeeDrops = xrpToDrops($netFeeXrp);
    $baseFee = BigDecimal::of($netFeeDrops);

    if ($tx['TransactionType'] === 'EscrowFinish' && isset($tx['Fulfillment']) && !is_null($tx['Fulfillment'])) {
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
    $tx['Fee'] = (string) $totalFee->toScale(0, RoundingMode::CEILING);
}

/**
 * @param string $value
 * @param int|float $multiplier
 * @return BigDecimal
 * @throws MathException
 */
function scaleValue (string $value, int|float $multiplier): BigDecimal
{
    return BigDecimal::of($value)->multipliedBy($multiplier);
}

/**
 * @param JsonRpcClient $client
 * @param array $tx
 * @return void
 */
function setLatestValidatedLedgerSequence (JsonRpcClient $client, array &$tx): void
{
    $ledgerSequence = $client->getLedgerIndex();
    $ledgerOffset = 20;
    $tx['LastLedgerSequence'] = $ledgerSequence + $ledgerOffset;
}

/**
 * @param JsonRpcClient $client
 * @param array $tx
 * @return void
 * @throws Exception
 */
function checkAccountDeleteBlockers (JsonRpcClient $client, array &$tx): void
{
    $accountObjectsRequest = new AccountObjectsRequest(
        account: $tx['Account'],
        ledgerIndex: 'validated',
        deletionBlockersOnly: true
    );

    $accountObjectsResponse = $client->request($accountObjectsRequest)->wait();

    if ($accountObjectsResponse->getResult()['account_objects']['length'] > 0) {
        throw new Exception("Account {$tx['Account']} cannot be deleted; there are Escrows, PayChannels, RippleStates, or Checks associated with the account.");
    }
}

if (! function_exists('XRPL_PHP\Sugar\autofill')) {

    /**
     * @param JsonRpcClient $client
     * @param Transaction|string|array $transaction
     * @param int|null $signersCount
     * @return array
     * @throws Exception
     */
    function autofill(
        JsonRpcClient $client,
        Transaction|string|array $transaction,
        ?int $signersCount = null
    ): array
    {
        if (is_string($transaction)) {
            $binaryCodec = new BinaryCodec();
            $tx = $binaryCodec->decode($transaction);
        } else if ($transaction instanceof Transaction) {
            $tx = $transaction->toArray();
        } else {
            $tx = $transaction;
        }

        setValidAddresses($tx);

        //TODO: check function
        //setTransactionFlagsToNumber($tx);

        if (!isset($tx['Sequence'])) {
            setNextValidSequenceNumber($client, $tx);
        }

        if (!isset($tx['Fee'])) {
            calculateFeePerTransactionType($client, $tx, $signersCount);
        }

        if (!isset($tx['LastLedgerSequence'])) {
            setLatestValidatedLedgerSequence($client, $tx);
        }

        if (!isset($tx['TransactionType'])) {
            checkAccountDeleteBlockers($client, $tx);
        }

        if (empty($tx['SourceTag'])) {
            unset($tx['SourceTag']);
        }
        if (empty($tx['DestinationTag'])) {
            unset($tx['DestinationTag']);
        }

        return $tx;
    }
}
