<?php

namespace XRPL_PHP\Sugar;

use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use XRPL_PHP\Models\ServerInfo\SubmitRequest;
use XRPL_PHP\Models\ServerInfo\SubmitResponse;
use XRPL_PHP\Models\Transaction\TransactionTypes\BaseTransaction as Transaction;
use XRPL_PHP\Models\Transaction\TxRequest;
use XRPL_PHP\Models\Transaction\TxResponse;
use XRPL_PHP\Utils\Hashes\HashLedger;
use XRPL_PHP\Wallet\Wallet;

const LEDGER_CLOSE_TIME = 4; //Seconds

function submitRequest(
    JsonRpcClient $client,
    mixed $signedTransaction,
    bool $failHard = false
): PromiseInterface
{
    if (!isSigned($signedTransaction)) {
        throw new Exception('Transaction must be signed');
    }

    $binaryCodec = new BinaryCodec();
    if (is_string($signedTransaction)) {
        $signedTxEncoded = $signedTransaction;
    } else if (is_object($signedTransaction) && get_class($signedTransaction) === Transaction::class) {
        $signedTxEncoded = $binaryCodec->encode($signedTransaction->toArray());
    } else {
        $signedTxEncoded = $binaryCodec->encode($signedTransaction);
    }

    $submitRequest = new SubmitRequest(
        tx_blob: $signedTxEncoded,
        fail_hard: isAccountDelete($signedTransaction) || $failHard
    );

    return $client->request($submitRequest);
}

/**
 * The core logic of reliable submission. This polls the ledger until the result of the
 * transaction can be considered final, meaning it has either been included in a
 * validated ledger, or the transaction's lastLedgerSequence has been surpassed by the
 * latest ledger sequence (meaning it will never be included in a validated ledger).
 *
 * @param JsonRpcClient $client
 * @param string $txHash
 * @param int $lastLedger
 * @param string $submissionResult
 * @return TxResponse
 * @throws Exception
 */
function waitForFinalTransactionOutcome(
    JsonRpcClient $client,
    string $txHash,
    int $lastLedger,
    string $submissionResult
): TxResponse
{
    sleep(LEDGER_CLOSE_TIME);

    $latestLedger = $client->getLedgerIndex();

    if ($lastLedger < $latestLedger) {
        throw new Exception("The latest ledger sequence {$latestLedger} is greater than the transaction's LastLedgerSequence ({$lastLedger})."
            . PHP_EOL ."Preliminary result: {$submissionResult}");
    }

    $txRequest = new TxRequest($txHash);
    $txResponse = $client->request($txRequest)->wait();

    if ($txResponse::class === 'XRPL_PHP\Models\ErrorResponse') {
        if ($txResponse->getError() === 'txnNotFound') {
            return waitForFinalTransactionOutcome(
                $client,
                $txHash,$lastLedger,
                $submissionResult
            );
        }

        throw new Exception ("{$txResponse->getError()}"
            . PHP_EOL . "Preliminary result: {$submissionResult}"
            . PHP_EOL . "Full error details: " .print_r($txResponse, true)
        );
    }

    if ($txResponse->getResult()['validated']) {
        return $txResponse;
    }

    return waitForFinalTransactionOutcome(
      $client,
      $txHash,$lastLedger,
      $submissionResult
    );
}

/**
 * Checks if the transaction has been signed
 *
 * @param Transaction|string|array $transaction
 * @return bool
 */
function isSigned(Transaction|string|array $transaction): bool
{
    if (is_string($transaction)) {
        $binaryCodec = new BinaryCodec();
        $tx = $binaryCodec->decode($transaction);
    } else if (is_object($transaction) && get_class($transaction) === Transaction::class) {
        $tx = $transaction->toArray();
    } else {
        $tx = $transaction;
    }

    return (!empty($tx['SigningPubKey']) || !empty($tx['TxnSignature']));
}

/**
 * Initializes a transaction for a submit request
 *
 * @param JsonRpcClient $client
 * @param Transaction|string|array $transaction
 * @param bool|null $autofill
 * @param bool|null $failHard
 * @param Wallet|null $wallet
 * @return mixed
 * @throws Exception
 */
function getSignedTx(
    JsonRpcClient $client,
    Transaction|string|array $transaction,
    ?bool $autofill,
    ?bool $failHard,
    ?Wallet $wallet
): string
{
    // Cannot use defaults here like in JavaScript
    if (is_null($autofill)) {
        $autofill = true;
    }

    if (isSigned($transaction)) {
        return $transaction;
    }

    if(is_null($wallet)) {
        throw new Exception('Wallet must be provided when submitting an unsigned transaction');
    }

    if (is_string($transaction)) {
        $binaryCodec = new BinaryCodec();
        $tx = $binaryCodec->decode($transaction);
    } else if (is_object($transaction) && get_class($transaction) === Transaction::class) {
        $tx = $transaction->toArray();
    } else {
        $tx = $transaction;
    }

    if ($autofill) {
        $tx = autofill($client, $tx);
    }

    return $wallet->sign($tx)['tx_blob'];
}

/**
 * Checks if there is a LastLedgerSequence as a part of the transaction
 *
 * @param array $tx
 * @return int|null
 */
function getLastLedgerSequence(array|string $tx): int|null
{
    if (is_string($tx)) {
        $binaryCodec = new BinaryCodec();
        $tx = $binaryCodec->decode($tx);
    }

    return (isset($tx['LastLedgerSequence'])) ? (int)$tx['LastLedgerSequence'] : null;
}

/**
 * Checks if the transaction is an AccountDelete transaction
 *
 * @param array $tx
 * @return bool
 */
function isAccountDelete(array|string $tx): bool
{
    if (is_string($tx)) {
        $binaryCodec = new BinaryCodec();
        $tx = $binaryCodec->decode($tx);
    }

    return($tx['TransactionType'] === 'AccountDelete');
}

if (! function_exists('XRPL_PHP\Sugar\submit')) {

    function submit(
        JsonRpcClient $client,
        Transaction|string|array $transaction,
        ?bool $autofill,
        ?bool $failHard,
        ?Wallet $wallet
    ): PromiseInterface
    {
        $signedTx = getSignedTx($client, $transaction, $autofill, $failHard, $wallet);

        return submitRequest($client, $signedTx, $failHard);
    }
}

if (! function_exists('XRPL_PHP\Sugar\submit')) {

    /**
     * @param JsonRpcClient $client
     * @param Transaction|string|array $transaction
     * @param bool|null $autofill
     * @param bool|null $failHard
     * @param Wallet|null $wallet
     * @return array
     * @throws Exception
     */
    function submit(
        JsonRpcClient $client,
        Transaction|string|array $transaction,
        ?bool $autofill,
        ?bool $failHard,
        ?Wallet $wallet
    ): SubmitResponse
    {
        $signedTx = getSignedTx($client, $transaction, $autofill, $failHard, $wallet);

        return submitRequest($client, $signedTx, $failHard)->wait();
    }
}

if (! function_exists('XRPL_PHP\Sugar\submitAndWait')) {

    /**
     * @param JsonRpcClient $client
     * @param Transaction|string|array $transaction
     * @param bool|null $autofill
     * @param bool|null $failHard
     * @param Wallet|null $wallet
     * @return array
     * @throws Exception
     */
    function submitAndWait(
        JsonRpcClient $client,
        Transaction|string|array $transaction,
        ?bool $autofill,
        ?bool $failHard,
        ?Wallet $wallet
    ): TxResponse
    {
        $signedTx = getSignedTx($client, $transaction, $autofill, $failHard, $wallet);

        $lastLedger = getLastLedgerSequence($signedTx);
        if(is_null($lastLedger)) {
            throw new Exception('Transaction must contain a LastLedgerSequence value for reliable submission.');
        }

        $response = submitRequest($client, $signedTx, $failHard)->wait();

        $txHash = HashLedger::hashSignedTx($signedTx);

        return waitForFinalTransactionOutcome(
            $client,
            $txHash,
            $lastLedger,
            $response->getResult()['engine_result']
        );
    }
}