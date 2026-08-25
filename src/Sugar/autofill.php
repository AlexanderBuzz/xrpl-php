<?php

namespace Hardcastle\XRPL_PHP\Sugar;

use Brick\Math\BigDecimal;
use Exception;
use Hardcastle\XRPL_PHP\Client\Autofiller;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\BaseTransaction as Transaction;

/**
 * Thin wrappers around Hardcastle\XRPL_PHP\Client\Autofiller.
 *
 * The logic moved into that class; these functions remain so that existing
 * code keeps working. They will be removed in a future major version.
 */

/**
 * @deprecated Use Autofiller::getClassicAccountAndTag()
 */
function getClassicAccountAndTag(string $account, ?int $expectedTag = null): array
{
    return Autofiller::getClassicAccountAndTag($account, $expectedTag);
}

/**
 * @deprecated Use Autofiller::scaleValue()
 */
function scaleValue(string $value, int|float $multiplier): BigDecimal
{
    return Autofiller::scaleValue($value, $multiplier);
}

/**
 * @deprecated Use Autofiller::setValidAddresses()
 */
function setValidAddresses(array &$tx): void
{
    (new Autofiller(new JsonRpcClient('https://xrplcluster.com')))->setValidAddresses($tx);
}

/**
 * @deprecated Use Autofiller::setNextValidSequenceNumber()
 */
function setNextValidSequenceNumber(JsonRpcClient $client, array &$tx): void
{
    (new Autofiller($client))->setNextValidSequenceNumber($tx);
}

/**
 * @deprecated Use Autofiller::fetchOwnerReserveFee()
 */
function fetchOwnerReserveFee(JsonRpcClient $client): BigDecimal
{
    return (new Autofiller($client))->fetchOwnerReserveFee();
}

/**
 * @deprecated Use Autofiller::fetchOwnerReserveFee(); the fee is not specific
 *             to AccountDelete
 */
function fetchAccountDeleteFee(JsonRpcClient $client): BigDecimal
{
    return (new Autofiller($client))->fetchOwnerReserveFee();
}

/**
 * @deprecated Use Autofiller::calculateFeePerTransactionType()
 */
function calculateFeePerTransactionType(JsonRpcClient $client, array &$tx, ?int $signersCount = 0): void
{
    (new Autofiller($client))->calculateFeePerTransactionType($tx, $signersCount);
}

/**
 * @deprecated Use Autofiller::setLatestValidatedLedgerSequence()
 */
function setLatestValidatedLedgerSequence(JsonRpcClient $client, array &$tx): void
{
    (new Autofiller($client))->setLatestValidatedLedgerSequence($tx);
}

/**
 * @deprecated Use Autofiller::checkAccountDeleteBlockers()
 */
function checkAccountDeleteBlockers(JsonRpcClient $client, array &$tx): void
{
    (new Autofiller($client))->checkAccountDeleteBlockers($tx);
}

if (! function_exists('Hardcastle\XRPL_PHP\Sugar\autofill')) {

    /**
     * @deprecated Use JsonRpcClient::autofill() or Autofiller::autofill()
     *
     * @throws Exception
     */
    function autofill(
        JsonRpcClient $client,
        Transaction|string|array $transaction,
        ?int $signersCount = null
    ): array
    {
        return (new Autofiller($client))->autofill($transaction, $signersCount);
    }
}
