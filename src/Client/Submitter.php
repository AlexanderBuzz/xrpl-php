<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Client;

use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions\Definitions;
use Hardcastle\XRPL_PHP\Models\ErrorResponse;
use Hardcastle\XRPL_PHP\Models\Transaction\SubmitRequest;
use Hardcastle\XRPL_PHP\Models\Transaction\SubmitResponse;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\BaseTransaction as Transaction;
use Hardcastle\XRPL_PHP\Models\Transaction\TxRequest;
use Hardcastle\XRPL_PHP\Models\Transaction\TxResponse;
use Hardcastle\XRPL_PHP\Utils\Hashes\HashLedger;
use Hardcastle\XRPL_PHP\Wallet\Wallet;

/**
 * Submits transactions and, for reliable submission, polls the ledger until
 * their outcome is final.
 *
 * This is the object form of the Sugar\submit() family, which stays available
 * as deprecated wrappers.
 */
class Submitter
{
    /**
     * Seconds to wait between polls, roughly one ledger close.
     */
    public const LEDGER_CLOSE_TIME = 3;

    public function __construct(private readonly JsonRpcClient $client)
    {
    }

    /**
     * Submit a signed transaction.
     *
     * @param array $signedTransaction
     * @param bool|null $failHard
     * @return PromiseInterface
     * @throws Exception
     */
    public function submitRequest(array $signedTransaction, ?bool $failHard = false): PromiseInterface
    {
        if (!self::isSigned($signedTransaction)) {
            throw new Exception('Transaction must be signed');
        }

        $definitions = $this->client->getDefinitions();

        $submitRequest = new SubmitRequest(
            txBlob: (new BinaryCodec($definitions))->encode($signedTransaction),
            failHard: self::isAccountDelete($signedTransaction, $definitions) || $failHard
        );

        return $this->client->request($submitRequest);
    }

    /**
     * Submit a transaction and return as soon as the server has taken it.
     *
     * The result is rippled's preliminary opinion, not an outcome: a
     * tesSUCCESS here can still fail once the transaction is applied. Use
     * submitAndWait() when the outcome matters.
     *
     * @param Transaction|array|string $transaction
     * @param bool|null $autofill Fill in Sequence, Fee and LastLedgerSequence first
     * @param bool|null $failHard Refuse to retry the transaction in later ledgers
     * @param Wallet|null $wallet Required unless the transaction is already signed
     * @return SubmitResponse
     * @throws Exception
     */
    public function submit(
        Transaction|array|string $transaction,
        ?bool $autofill = false,
        ?bool $failHard = false,
        ?Wallet $wallet = null
    ): SubmitResponse {
        $signedTx = $this->getSignedTx($transaction, $autofill, $wallet);

        return $this->submitRequest($signedTx, $failHard)->wait();
    }

    /**
     * Submit and wait until the outcome is final.
     *
     * @param Transaction|array|string $transaction
     * @param bool|null $autofill
     * @param bool|null $failHard
     * @param Wallet|null $wallet
     * @return TxResponse
     * @throws Exception
     */
    public function submitAndWait(
        Transaction|array|string $transaction,
        ?bool $autofill = false,
        ?bool $failHard = false,
        ?Wallet $wallet = null
    ): TxResponse {
        $definitions = $this->client->getDefinitions();
        $signedTx = $this->getSignedTx($transaction, $autofill, $wallet);

        $lastLedger = self::getLastLedgerSequence($signedTx, $definitions);
        if (is_null($lastLedger)) {
            throw new Exception('Transaction must contain a LastLedgerSequence value for reliable submission.');
        }

        $response = $this->submitRequest($signedTx, $failHard)->wait();

        return $this->waitForFinalTransactionOutcome(
            HashLedger::hashSignedTx($signedTx, $definitions),
            $lastLedger,
            $response->getResult()['engine_result']
        );
    }

    /**
     * Poll until the transaction is in a validated ledger, or until its
     * LastLedgerSequence has been passed and it never can be.
     *
     * @param string $txHash
     * @param int $lastLedger
     * @param string $submissionResult
     * @return TxResponse
     * @throws Exception
     */
    public function waitForFinalTransactionOutcome(
        string $txHash,
        int $lastLedger,
        string $submissionResult
    ): TxResponse {
        sleep(self::LEDGER_CLOSE_TIME);

        $latestLedger = $this->client->getLedgerIndex();

        if ($lastLedger < $latestLedger) {
            throw new Exception(
                "The latest ledger sequence {$latestLedger} is greater than the transaction's LastLedgerSequence ({$lastLedger})."
                . PHP_EOL . "Preliminary result: {$submissionResult}"
            );
        }

        $txResponse = $this->client->request(new TxRequest($txHash))->wait();

        if ($txResponse instanceof ErrorResponse) {
            if ($txResponse->getError() === 'txnNotFound') {
                return $this->waitForFinalTransactionOutcome($txHash, $lastLedger, $submissionResult);
            }

            throw new Exception(
                "{$txResponse->getError()}"
                . PHP_EOL . "Preliminary result: {$submissionResult}"
                . PHP_EOL . "Full error details: " . print_r($txResponse, true)
            );
        }

        if ($txResponse->getResult()['validated']) {
            return $txResponse;
        }

        return $this->waitForFinalTransactionOutcome($txHash, $lastLedger, $submissionResult);
    }

    /**
     * Turn whatever was handed in into a signed transaction array.
     *
     * @param Transaction|string|array $transaction
     * @param bool|null $autofill
     * @param Wallet|null $wallet
     * @return array
     * @throws Exception
     */
    public function getSignedTx(
        Transaction|string|array $transaction,
        ?bool $autofill = false,
        ?Wallet $wallet = null
    ): array {
        $definitions = $this->client->getDefinitions();

        if (is_string($transaction)) {
            $tx = (new BinaryCodec($definitions))->decode($transaction);
        } else if ($transaction instanceof Transaction) {
            $tx = $transaction->toArray();
        } else {
            $tx = $transaction;
        }

        if (self::isSigned($tx)) {
            return $tx;
        }

        if (is_null($wallet)) {
            throw new Exception('Wallet must be provided when submitting an unsigned transaction');
        }

        if ($autofill) {
            $tx = $this->client->getAutofiller()->autofill($tx);
        }

        // Wallet::sign() returns a tx_blob/hash envelope, while every caller
        // here expects a transaction array - the same shape the already-signed
        // branch above returns.
        return (new BinaryCodec($definitions))->decode($wallet->sign($tx)['tx_blob']);
    }

    /**
     * Whether a transaction carries a signature.
     *
     * A single-signed transaction has a SigningPubKey, a multi-signed one has
     * Signers and an empty SigningPubKey, so either field is enough.
     *
     * @param array $tx
     * @return bool
     */
    public static function isSigned(array $tx): bool
    {
        return (!empty($tx['SigningPubKey']) || !empty($tx['TxnSignature']));
    }

    /**
     * The ledger after which the transaction can no longer be included, or
     * null if it carries no such limit.
     *
     * @param array|string $tx A transaction array or a tx_blob
     * @param Definitions|null $definitions Needed to decode a blob of another network
     * @return int|null
     * @throws Exception
     */
    public static function getLastLedgerSequence(array|string $tx, ?Definitions $definitions = null): int|null
    {
        if (is_string($tx)) {
            // Decoding resolves every field in the blob, so a transaction from
            // another network needs that network's definitions even though
            // LastLedgerSequence itself carries the same ordinal everywhere.
            $tx = (new BinaryCodec($definitions))->decode($tx);
        }

        return isset($tx['LastLedgerSequence']) ? (int)$tx['LastLedgerSequence'] : null;
    }

    /**
     * Whether this is an AccountDelete.
     *
     * Those are submitted with failHard, because a deletion that is retried in
     * a later ledger would burn the owner reserve again.
     *
     * @param array|string $tx A transaction array or a tx_blob
     * @param Definitions|null $definitions Needed to decode a blob of another network
     * @return bool
     * @throws Exception
     */
    public static function isAccountDelete(array|string $tx, ?Definitions $definitions = null): bool
    {
        if (is_string($tx)) {
            $tx = (new BinaryCodec($definitions))->decode($tx);
        }

        return ($tx['TransactionType'] ?? null) === 'AccountDelete';
    }
}
