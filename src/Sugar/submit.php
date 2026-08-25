<?php

namespace Hardcastle\XRPL_PHP\Sugar;

use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Client\Submitter;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions\Definitions;
use Hardcastle\XRPL_PHP\Models\Transaction\SubmitResponse;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\BaseTransaction as Transaction;
use Hardcastle\XRPL_PHP\Models\Transaction\TxResponse;
use Hardcastle\XRPL_PHP\Wallet\Wallet;

/**
 * Thin wrappers around Hardcastle\XRPL_PHP\Client\Submitter.
 *
 * The logic moved into that class; these functions remain so that existing
 * code keeps working. They will be removed in a future major version.
 */

const LEDGER_CLOSE_TIME = Submitter::LEDGER_CLOSE_TIME;

/**
 * @deprecated Use Submitter::submitRequest()
 * @throws Exception
 */
function submitRequest(
    JsonRpcClient $client,
    array $signedTransaction,
    ?bool $failHard = false
): PromiseInterface
{
    return (new Submitter($client))->submitRequest($signedTransaction, $failHard);
}

/**
 * @deprecated Use Submitter::waitForFinalTransactionOutcome()
 * @throws Exception
 */
function waitForFinalTransactionOutcome(
    JsonRpcClient $client,
    string $txHash,
    int $lastLedger,
    string $submissionResult
): TxResponse
{
    return (new Submitter($client))->waitForFinalTransactionOutcome($txHash, $lastLedger, $submissionResult);
}

/**
 * @deprecated Use Submitter::isSigned()
 */
function isSigned(array $tx): bool
{
    return Submitter::isSigned($tx);
}

/**
 * @deprecated Use Submitter::getSignedTx()
 * @throws Exception
 */
function getSignedTx(
    JsonRpcClient $client,
    Transaction|string|array $transaction,
    ?bool $autofill = false,
    ?Wallet $wallet = null
): array
{
    return (new Submitter($client))->getSignedTx($transaction, $autofill, $wallet);
}

/**
 * @deprecated Use Submitter::getLastLedgerSequence()
 * @throws Exception
 */
function getLastLedgerSequence(array|string $tx, ?Definitions $definitions = null): int|null
{
    return Submitter::getLastLedgerSequence($tx, $definitions);
}

/**
 * @deprecated Use Submitter::isAccountDelete()
 * @throws Exception
 */
function isAccountDelete(array|string $tx, ?Definitions $definitions = null): bool
{
    return Submitter::isAccountDelete($tx, $definitions);
}

if (! function_exists('Hardcastle\XRPL_PHP\Sugar\submit')) {

    /**
     * @deprecated Use JsonRpcClient::submit() or Submitter::submit()
     * @throws Exception
     */
    function submit(
        JsonRpcClient $client,
        Transaction|array|string $transaction,
        ?bool $autofill,
        ?bool $failHard,
        ?Wallet $wallet
    ): SubmitResponse
    {
        return (new Submitter($client))->submit($transaction, $autofill, $failHard, $wallet);
    }
}

if (! function_exists('Hardcastle\XRPL_PHP\Sugar\submitAndWait')) {

    /**
     * @deprecated Use JsonRpcClient::submitAndWait() or Submitter::submitAndWait()
     * @throws Exception
     */
    function submitAndWait(
        JsonRpcClient $client,
        Transaction|array|string $transaction,
        ?bool $autofill = false,
        ?bool $failHard = false,
        ?Wallet $wallet = null
    ): TxResponse
    {
        return (new Submitter($client))->submitAndWait($transaction, $autofill, $failHard, $wallet);
    }
}
