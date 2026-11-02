<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Core\RippleBinaryCodec;

use Exception;
use Hardcastle\XRPL_PHP\Core\HashPrefix;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt64;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions\Definitions;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\AccountId;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\StObject;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt32;
use Hardcastle\XRPL_PHP\Utils\Hashes\HashLedger;

/**
 * Encodes transactions to their binary form and back.
 *
 * encode() produces what gets submitted, encodeForSigning() the reduced form
 * that a signature covers - fields marked as not being signing fields, such as
 * TxnSignature and BatchSigners, are left out of it.
 */
class BinaryCodec extends Binary
{
    const TRANSACTION_SIGN = '53545800';

    /**
     * Serializes an array (or JSON string) into a hex-encoded string.
     *
     * @param string|array $jsonObject
     * @return string
     */
    public function encode(string|array $jsonObject): string
    {
        if (is_array($jsonObject)) {
            $jsonObject = json_encode($jsonObject);
        }

        return StObject::fromJson($jsonObject, $this->getDefinitions())->toString();
    }

    /**
     * Deserializes a hex-encoded string into an array.
     *
     * @param string $binaryString
     * @return array
     * @throws Exception
     */
    public function decode(string $binaryString): array
    {
        if (str_starts_with(trim(bin2hex(hex2bin($binaryString))), "7b")) {
            throw new Exception("The provided binary string appears to be a hex-encoded JSON object, not a binary-encoded transaction.");
        }

        return $this->binaryToJson($binaryString);
    }

    /**
     * Encode a transaction and prepare for signing
     *
     * @param string|array $tx
     * @return string
     */
    public function encodeForSigning(string|array $tx): string
    {
        if (is_string($tx)) {
            $tx = json_decode($tx, true);
        }
        $filtered = $this->removeNonSigningFields($tx);

        return self::TRANSACTION_SIGN . $this->encode($filtered);
    }

    /**
     * The bytes a payment channel claim is signed over.
     *
     * A claim is not a transaction. It is the prefix 'CLM\0', the 32 byte
     * channel ID and the amount in drops as a big-endian UInt64, and the
     * signature over exactly that is what PaymentChannelClaim carries in its
     * Signature field. Mirrors encodeForSigningClaim in ripple-binary-codec.
     *
     * @param array{channel: string, amount: string|int} $claim the channel ID
     *   as 64 hex characters and the amount in drops
     * @return string upper case hex
     * @throws Exception on a malformed channel ID or amount
     */
    public function encodeForSigningClaim(array $claim): string
    {
        $channel = $claim['channel'];
        $amount = (string) $claim['amount'];

        if (!preg_match('/^[0-9A-Fa-f]{64}$/', $channel)) {
            throw new Exception("Channel ID has to be 64 hex characters, got: {$channel}");
        }
        if (!preg_match('/^[0-9]+$/', $amount)) {
            throw new Exception("Claim amount has to be a number of drops, got: {$amount}");
        }

        $prefix = dechex(HashPrefix::PAYMENT_CHANNEL_CLAIM);
        $drops = UnsignedInt64::fromBase10($amount)->toBytes()->toString('hex');

        return strtoupper($prefix . $channel . $drops);
    }

    /**
     * The bytes a BatchSigner signs over (XLS-56, Batch V1.1).
     *
     * Not the transaction: the prefix 'BCH\0', the outer Account, the outer
     * sequence value (Sequence, or TicketSequence when Sequence is 0), the
     * outer Flags, the number of inner transactions and their transaction IDs
     * in order, then the BatchSigner's Account, and for a multi-signed entry
     * the individual signer's Account after it. Binding the outer Account and
     * sequence is what keeps a BatchSigners entry from being replayed against
     * another Batch. Mirrors encodeForSigningBatch in ripple-binary-codec and
     * serializeBatch() in rippled.
     *
     * The inner transaction IDs are computed here from RawTransactions with
     * these definitions, so the Batch has to be complete: every inner
     * transaction with its Sequence or TicketSequence, Fee "0", empty
     * SigningPubKey and the tfInnerBatchTxn flag, exactly as it will be
     * submitted. Any later change to an inner transaction invalidates every
     * signature.
     *
     * @param array $batch the Batch transaction
     * @param string $batchAccount the BatchSigner's Account
     * @param string|null $signerAccount the signer's Account, for a multi-signed BatchSigner
     * @return string upper case hex
     * @throws Exception on anything but a complete Batch
     */
    public function encodeForSigningBatch(array $batch, string $batchAccount, ?string $signerAccount = null): string
    {
        if (($batch['TransactionType'] ?? null) !== 'Batch') {
            throw new Exception('encodeForSigningBatch needs a Batch transaction');
        }
        if (!isset($batch['Account']) || !isset($batch['Flags'])) {
            throw new Exception('A Batch needs Account and Flags before it can be signed');
        }
        $rawTransactions = $batch['RawTransactions'] ?? [];
        if (!is_array($rawTransactions) || $rawTransactions === []) {
            throw new Exception('A Batch needs RawTransactions before it can be signed');
        }

        $payload = strtoupper(dechex(HashPrefix::BATCH))
            . AccountId::fromJson((string) $batch['Account'])->toString()
            . UnsignedInt32::fromJson(self::batchSequenceValue($batch))->toBytes()->toString('hex')
            . UnsignedInt32::fromJson((int) $batch['Flags'])->toBytes()->toString('hex')
            . UnsignedInt32::fromJson(count($rawTransactions))->toBytes()->toString('hex');

        foreach ($this->innerTransactionIds($batch) as $transactionId) {
            $payload .= $transactionId;
        }

        $payload .= AccountId::fromJson($batchAccount)->toString();
        if ($signerAccount !== null) {
            $payload .= AccountId::fromJson($signerAccount)->toString();
        }

        return strtoupper($payload);
    }

    /**
     * The transaction IDs of a Batch's inner transactions, in order, computed
     * with these definitions.
     *
     * @param array $batch
     * @return string[] upper case hex
     * @throws Exception when an entry is not a RawTransaction wrapper
     */
    public function innerTransactionIds(array $batch): array
    {
        $ids = [];
        foreach ($batch['RawTransactions'] ?? [] as $index => $wrapper) {
            if (!is_array($wrapper) || !isset($wrapper['RawTransaction']) || !is_array($wrapper['RawTransaction'])) {
                throw new Exception("RawTransactions[{$index}] is not a RawTransaction wrapper");
            }
            $ids[] = HashLedger::hashTx($wrapper['RawTransaction'], $this->getDefinitions());
        }

        return $ids;
    }

    /**
     * The sequence value a Batch's signers bind to: Sequence, or
     * TicketSequence when Sequence is 0 or absent.
     */
    public static function batchSequenceValue(array $batch): int
    {
        $sequence = (int) ($batch['Sequence'] ?? 0);

        return $sequence !== 0 ? $sequence : (int) ($batch['TicketSequence'] ?? 0);
    }

    /**
     * Encode a transaction and prepare it for multi-signing
     *
     * @param string|array $tx
     * @param string $signAs
     * @return string
     * @throws Exception
     */
    public function encodeForMultisigning(string|array $tx, string $signAs): string
    {
        if (is_string($tx)) {
            $tx = json_decode($tx, true);
        }

        if ($tx['SigningPubKey'] !== '') {
            throw new Exception('Error trying to encode transaction for multisignign');
        }

        $filtered = $this->removeNonSigningFields($tx);

        $prefix = dechex(HashPrefix::TRANSACTION_MULTISIGN);
        $suffix = AccountId::fromJson($signAs)->toString();

        return $prefix . $this->encode($filtered) . $suffix;
    }

    /*
    public function encodeQuality(string $value): string
    {
        //TODO: implement function
        //return quality.encode(value).toString('hex').toUpperCase()
    }

    public function decodeQuality(string $value): string
    {
        //TODO: implement function
        return quality.decode(value).toString()
    }

    public function decodeLedgerData()
    {
        //todo: implement function
        //import { decodeLedgerData } from './ledger-hashes'
    }
    */

    /**
     * Remove fields from a tx array that will not be signed.
     *
     * @param array $jsonObject
     * @return array
     */
    private function removeNonSigningFields(array $jsonObject): array
    {
        foreach ($jsonObject as $fieldName => $value) {
            if (!$this->isSigningField($fieldName)) {
                unset($jsonObject[$fieldName]);
            }
        }

        return $jsonObject;
    }

    /**
     * Checks if a given field is included in the fields that constitute the part of the tx that will be signed.
     *
     * @param string $fieldName
     * @return bool
     */
    private function isSigningField(string $fieldName): bool
    {
        return $this->getDefinitions()->getFieldInstance($fieldName)->isSigningField();
    }
}