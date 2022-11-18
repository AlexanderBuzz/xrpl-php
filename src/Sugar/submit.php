<?php

namespace XRPL_PHP\Sugar;

use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use XRPL_PHP\Client\JsonRpcClient;
use GuzzleHttp\Promise\Promise;
use XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use XRPL_PHP\Models\Account\AccountInfoRequest;
use XRPL_PHP\Models\Account\AccountLinesRequest;
use XRPL_PHP\Models\Methods\SubmitRequest;
use XRPL_PHP\Models\Transactions\Transaction;
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
    } else if (get_class($signedTransaction) === Transaction::class) {
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

function waitForFinalTransactionOutcome(
    JsonRpcClient $client,
    string $txHash,
    int $lastLedger,
    string $submissionResult
): PromiseInterface
{
    sleep(LEDGER_CLOSE_TIME);

    $latestLedger = $client->getLedgerIndex();

    if ($lastLedger > $latestLedger) {
        throw new Exception("The latest ledger sequence {$latestLedger} is greater than the transaction's LastLedgerSequence ({$lastLedger})."
            . PHP_EOL ."Preliminary result: {$submissionResult}");
    }


}

function isSigned(Transaction|string|array $transaction): bool
{
    if (is_string($transaction)) {
        $binaryCodec = new BinaryCodec();
        $tx = $binaryCodec->decode($transaction);
    } else if (get_class($transaction) === Transaction::class) {
        $tx = $transaction->toArray();
    } else {
        $tx = $transaction;
    }

    return (!is_null($tx['SigningPubKey']) || !is_null($tx['TxnSignature']));
}

function getSignedTx(
    JsonRpcClient $client,
    Transaction|string|array $transaction,
    ?bool $autofill,
    ?bool $failHard,
    ?Wallet $wallet
): mixed //TODO: Check for correct types!
{
    if (isSigned($transaction)) {
        return $transaction;
    }

    if(is_null($wallet)) {
        throw new Exception('Wallet must be provided when submitting an unsigned transaction');
    }

    if (is_string($transaction)) {
        $binaryCodec = new BinaryCodec();
        $tx = $binaryCodec->decode($transaction);
    } else if (get_class($transaction) === Transaction::class) {
        $tx = $transaction->toArray();
    } else {
        $tx = $transaction;
    }

    if ($autofill) {
        $tx = autofill($client, $tx);
    }

    return $wallet->sign($tx);
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
        $signedTx = getSignedTx($client, $transaction, $autofill, $failHard, $wallet)->wait();

        return submitRequest($client, $signedTx, $failHard);
    }
}

if (! function_exists('XRPL_PHP\Sugar\submitAndWait')) {

    function submitAndWait(
        JsonRpcClient $client,
        Transaction|string|array $transaction,
        ?bool $autofill,
        ?bool $failHard,
        ?Wallet $wallet
    ): array
    {
        $signedTx = getSignedTx($client, $transaction, $autofill, $failHard, $wallet)->wait();

        $lastLedger = getLastLedgerSequence($signedTx);
        if(is_null($lastLedger)) {
            throw new Exception('Transaction must contain a LastLedgerSequence value for reliable submission.');
        }

        $response = submitRequest($client, $signedTx, $failHard)->wait();

        //$txHash
    }
}