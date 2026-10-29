<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Wallet;

use Exception;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions\Definitions;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\AccountId;
use Hardcastle\XRPL_PHP\Core\RippleKeyPairs\Ed25519KeyPairService;
use Hardcastle\XRPL_PHP\Core\RippleKeyPairs\KeyPair;
use Hardcastle\XRPL_PHP\Core\RippleKeyPairs\KeyPairServiceInterface;
use Hardcastle\XRPL_PHP\Core\RippleKeyPairs\Secp256k1KeyPairService;
use Hardcastle\XRPL_PHP\Core\CoreUtilities as CoreUtilities;
use Hardcastle\XRPL_PHP\Utils\Utilities as XrplUtilities;
use Hardcastle\XRPL_PHP\Exceptions\ValidationException;
use Hardcastle\XRPL_PHP\Exceptions\XrplException;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\BaseTransaction as Transaction;
use Hardcastle\XRPL_PHP\Utils\Hashes\HashLedger;

/**
 * A key pair with the ability to sign.
 *
 * Signing runs through the binary codec, so a wallet for another network has to
 * be constructed with that network's definitions.
 */
class Wallet
{

    public const DEFAULT_ALGORITHM = KeyPair::EDDSA;

    private readonly BinaryCodec $binaryCodec;

    private KeyPairServiceInterface $keyPairService;

    private readonly string $publicKey;

    private string $classicAddress;

    /**
     * Build a wallet from an existing key pair.
     * Use generate() or fromSeed() unless the keys already exist elsewhere.
     */
    public function __construct(
        string $publicKey,
        private readonly string $privateKey,
        private readonly ?string $seed,
        ?string $masterAddress = null,
        ?Definitions $definitions = null,
    )
    {
        // Signing runs through the binary codec, so a wallet for another
        // network has to be built with that network's definitions.
        $this->binaryCodec = new BinaryCodec($definitions);

        $this->keyPairService = self::keyPairServiceFor($publicKey);
        $this->publicKey = $publicKey;

        if (is_string($masterAddress)) {
            $this->classicAddress = CoreUtilities::ensureClassicAddress($masterAddress);
        } else {
            $this->classicAddress = CoreUtilities::deriveAddress($publicKey);
        }
    }

    /**
     * Create a wallet from a fresh random seed.
     */
    public static function generate(
        string $type = self::DEFAULT_ALGORITHM,
        ?Definitions $definitions = null
    ): Wallet
    {
        $keyPairService = KeyPair::getKeyPairServiceByType($type);
        $seed = $keyPairService->generateSeed();

        return Wallet::fromSeed($seed, $definitions);
    }

    /**
     * Restore a wallet from its seed.
     * The algorithm follows from the seed itself, so a secp256k1 and an Ed25519
     * seed both work here.
     */
    public static function fromSeed(string $seed, ?Definitions $definitions = null): Wallet
    {
        return self::deriveWallet($seed, $definitions);
    }

    /**
     *
     *
     * @param string $seed
     * @return Wallet
     * @throws Exception
     */
    private static function deriveWallet(string $seed, ?Definitions $definitions = null): Wallet
    {
        $decoded = CoreUtilities::decodeSeed($seed);
        $keyPairService = KeyPair::getKeyPairServiceByType($decoded['type']);
        $keyPair = $keyPairService->deriveKeyPair($seed);

        return new Wallet(
            $keyPair->getPublicKey(),
            $keyPair->getPrivateKey(),
            $seed,
            null,
            $definitions
        );
    }

    /**
     * Signs a transaction.
     *
     * @param Transaction|array $transaction
     * @param string|bool $multisign
     * @return array
     * @throws ValidationException if the transaction is already signed or does not encode/decode to same result.
     * @throws XrplException if the issued currency being signed is XRP ignoring case.
     */
    public function sign(Transaction|array $transaction, string|bool $multisign = false): array
    {
        $multisignAddress = false;

        if (is_string($multisign) && str_starts_with($multisign, 'X')) {
            $multisignAddress = $multisign;
        } else if ($multisign) {
            $multisignAddress = $this->getClassicAddress();
        }

        if (!is_array($transaction)) {
            $tx = $transaction->toArray();
        } else {
            $tx = $transaction;
        }

        if (isset($tx['TxnSignature']) || isset($tx['Signers'])) {
            throw new ValidationException( 'txJSON must not contain "TxnSignature" or "Signers" properties');
        }

        //removetrailingzeros

        $tx['SigningPubKey'] = ($multisignAddress) ? '' : $this->publicKey;

        if ($multisignAddress) {
            $signer = [
                'Account' => $multisignAddress,
                'SigningPubKey' => $this->getPublicKey(),
                'TxnSignature' => $this->computeSignature(
                    $tx,
                    $this->getPrivateKey(),
                    $multisignAddress
                )
            ];
            $tx['Signers'] = [['Signer' => $signer]];
        } else {
            $signature = $this->computeSignature($tx, $this->getPrivateKey());
            $tx['TxnSignature'] = $signature;
        }

        $serializedTx = $this->binaryCodec->encode($tx);

        $this->checkTxSerialisation($serializedTx, $tx);

        $hash = HashLedger::hashSignedTx($serializedTx, $this->binaryCodec->getDefinitions());

        return [
            "tx_blob" => $serializedTx,
            "hash" => $hash,
        ];
    }

    /**
     * Sign a Batch as one of the accounts whose inner transactions it carries.
     *
     * A Batch with inner transactions from more than one account needs, in
     * BatchSigners, a signature from every account that would have had to
     * sign an inner transaction, except the outer Account, which signs the
     * Batch itself with sign(). This produces one such entry: it signs the
     * payload of encodeForSigningBatch() (outer Account, sequence value,
     * Flags and the inner transaction IDs, so the Batch must be complete and
     * autofilled before anyone signs) and returns the Batch with BatchSigners
     * holding this entry alone. Collect the entries of all signers with
     * combineBatchSigners(), then let the outer Account autofill the fee,
     * sign() and submit. Mirrors signMultiBatch in xrpl.js.
     *
     * The wallet signs for $batchAccount, its own address by default, which
     * has to be the Account (or the Delegate) of an inner transaction. With
     * $multisign the entry carries Signers instead of a signature, for a
     * $batchAccount that has a signer list: true signs as this wallet's
     * address, a string as that signer address.
     *
     * @param Transaction|array $batch the complete Batch
     * @param string|null $batchAccount the account to sign for, this wallet's by default
     * @param string|bool $multisign whether to sign as a signer of $batchAccount's signer list
     * @return array the Batch with this signer's BatchSigners entry
     * @throws ValidationException when this is not a Batch or $batchAccount has nothing to sign for
     * @throws Exception when the Batch is not complete
     */
    public function signBatch(Transaction|array $batch, ?string $batchAccount = null, string|bool $multisign = false): array
    {
        $tx = is_array($batch) ? $batch : $batch->toArray();

        if (($tx['TransactionType'] ?? null) !== 'Batch') {
            throw new ValidationException('signBatch() needs a Batch transaction');
        }

        $batchAccount = CoreUtilities::ensureClassicAddress($batchAccount ?? $this->getClassicAddress());

        if (!in_array($batchAccount, self::batchSignerAccounts($tx), true)) {
            throw new ValidationException(
                "{$batchAccount} has no inner transaction to sign for in this Batch"
            );
        }

        $signerAccount = null;
        if (is_string($multisign)) {
            $signerAccount = CoreUtilities::ensureClassicAddress($multisign);
        } elseif ($multisign) {
            $signerAccount = $this->getClassicAddress();
        }

        $payload = $this->binaryCodec->encodeForSigningBatch($tx, $batchAccount, $signerAccount);
        $signature = $this->keyPairService->sign($payload, $this->getPrivateKey());

        if ($signerAccount !== null) {
            $entry = [
                'Account' => $batchAccount,
                'Signers' => [[
                    'Signer' => [
                        'Account' => $signerAccount,
                        'SigningPubKey' => $this->getPublicKey(),
                        'TxnSignature' => $signature,
                    ],
                ]],
            ];
        } else {
            $entry = [
                'Account' => $batchAccount,
                'SigningPubKey' => $this->getPublicKey(),
                'TxnSignature' => $signature,
            ];
        }

        $tx['BatchSigners'] = [['BatchSigner' => $entry]];

        return $tx;
    }

    /**
     * Merge the BatchSigners of several copies of one Batch, each signed by
     * one party with signBatch(), into the Batch the outer Account signs.
     *
     * Every copy has to be the same Batch: same Account, sequence value,
     * Flags and inner transactions, each carrying BatchSigners and none of
     * them signed with sign() yet. The entries are sorted strictly ascending
     * by the numeric value of Account, which rippled enforces, an entry of
     * the outer Account is dropped (the outer signature covers it) and a
     * duplicate account keeps its first entry. Mirrors combineBatchSigners in
     * xrpl.js. The result still needs the outer signature: autofill the fee
     * (it depends on the number of signatures), sign(), submit.
     *
     * @param array<Transaction|array|string> $batches the signed copies, as arrays, models or tx_blobs
     * @param Definitions|null $definitions the definitions the Batch was built with
     * @return array the Batch with every BatchSigners entry
     * @throws ValidationException when the copies are not one and the same Batch
     * @throws Exception
     */
    public static function combineBatchSigners(array $batches, ?Definitions $definitions = null): array
    {
        if ($batches === []) {
            throw new ValidationException('There are no Batch transactions to combine');
        }

        $binaryCodec = new BinaryCodec($definitions);
        $decoded = [];
        foreach ($batches as $batch) {
            if (is_string($batch)) {
                $tx = $binaryCodec->decode($batch);
            } elseif (is_array($batch)) {
                $tx = $batch;
            } else {
                $tx = $batch->toArray();
            }

            if (($tx['TransactionType'] ?? null) !== 'Batch') {
                throw new ValidationException('Every transaction to combine has to be a Batch');
            }
            if (empty($tx['BatchSigners']) || !is_array($tx['BatchSigners'])) {
                throw new ValidationException('Every Batch to combine has to carry BatchSigners');
            }
            if (isset($tx['TxnSignature']) || isset($tx['Signers'])) {
                throw new ValidationException('A Batch to combine must not be signed yet');
            }

            $decoded[] = $tx;
        }

        $key = static fn (array $tx): string => (string) json_encode([
            $tx['Account'],
            BinaryCodec::batchSequenceValue($tx),
            (int) ($tx['Flags'] ?? 0),
            $binaryCodec->innerTransactionIds($tx),
        ]);
        $expected = $key($decoded[0]);
        foreach (array_slice($decoded, 1) as $tx) {
            if ($key($tx) !== $expected) {
                throw new ValidationException(
                    'Account, sequence, Flags and inner transactions have to be the same in every Batch to combine'
                );
            }
        }

        $outerAccount = $decoded[0]['Account'];
        $entries = [];
        foreach ($decoded as $tx) {
            foreach ($tx['BatchSigners'] as $entry) {
                if (($entry['BatchSigner']['Account'] ?? null) !== $outerAccount) {
                    $entries[] = $entry;
                }
            }
        }

        usort($entries, static fn (array $a, array $b): int => strcmp(
            AccountId::fromJson($a['BatchSigner']['Account'])->toString(),
            AccountId::fromJson($b['BatchSigner']['Account'])->toString()
        ));

        $combined = [];
        $lastAccount = null;
        foreach ($entries as $entry) {
            if ($entry['BatchSigner']['Account'] !== $lastAccount) {
                $combined[] = $entry;
                $lastAccount = $entry['BatchSigner']['Account'];
            }
        }

        $result = $decoded[0];
        $result['BatchSigners'] = $combined;

        return $result;
    }

    /**
     * The accounts a Batch needs a BatchSigners entry from: the Account of
     * every inner transaction, or its Delegate when it is delegated, its
     * Counterparty when it has one, and its Sponsor when the inner transaction
     * carries a SponsorSignature of a sponsor other than the outer Account.
     *
     * @param array $batch
     * @return string[] classic addresses
     */
    public static function batchSignerAccounts(array $batch): array
    {
        $accounts = [];
        foreach ($batch['RawTransactions'] ?? [] as $wrapper) {
            $inner = $wrapper['RawTransaction'] ?? [];
            if (!is_array($inner)) {
                continue;
            }
            if (isset($inner['Delegate'])) {
                $accounts[] = (string) $inner['Delegate'];
            } elseif (isset($inner['Account'])) {
                $accounts[] = (string) $inner['Account'];
            }
            if (isset($inner['Counterparty']) && is_string($inner['Counterparty'])) {
                $accounts[] = $inner['Counterparty'];
            }
            if (
                isset($inner['Sponsor'], $inner['SponsorSignature'])
                && $inner['Sponsor'] !== ($batch['Account'] ?? null)
            ) {
                $accounts[] = (string) $inner['Sponsor'];
            }
        }

        return array_values(array_unique($accounts));
    }

    /**
     * Verifies a signed transaction.
     *
     * @param string $signedTransaction A signed transaction (hex string of signTransaction result) to be verified offline.
     * @return bool Returns true if a signedTransaction is valid.
     */
    public function verifyTransaction(string $signedTransaction): bool
    {
        $tx = $this->binaryCodec->decode($signedTransaction);
        $messageHex = $this->binaryCodec->encodeForSigning($tx);
        $signature = $tx['TxnSignature'];

        return $this->keyPairService->verify($messageHex, $signature, $this->publicKey);
    }

    /**
     * Sign a payment channel claim, locally.
     *
     * A claim authorizes the channel's destination to take $amountDrops out
     * of the channel; it goes into the Signature field of a
     * PaymentChannelClaim, together with this wallet's public key. Claims are
     * not transactions, so this does not go through sign(), and unlike the
     * channel_authorize RPC method the secret never leaves the process.
     * Mirrors authorizeChannel in xrpl.js, which also takes drops.
     *
     * @param string $channelId the channel ID, 64 hex characters
     * @param string $amountDrops the cumulative amount in drops
     * @return string the signature as upper case hex
     * @throws Exception on a malformed channel ID or amount
     */
    public function signPaymentChannelClaim(string $channelId, string $amountDrops): string
    {
        $claimData = $this->binaryCodec->encodeForSigningClaim([
            'channel' => $channelId,
            'amount' => $amountDrops,
        ]);

        return $this->keyPairService->sign($claimData, $this->privateKey);
    }

    /**
     * Verify a payment channel claim, locally.
     *
     * The counterpart of signPaymentChannelClaim() for the channel's
     * destination, which holds the payer's public key (it is stored in the
     * channel) but not a wallet of theirs. The same check the channel_verify
     * RPC method makes, without the round trip.
     *
     * @param string $channelId the channel ID, 64 hex characters
     * @param string $amountDrops the cumulative amount in drops
     * @param string $signature the signature, hex
     * @param string $publicKey the public key of the channel's source, hex
     * @return bool
     * @throws Exception on a malformed channel ID, amount or public key
     */
    public static function verifyPaymentChannelClaim(
        string $channelId,
        string $amountDrops,
        string $signature,
        string $publicKey
    ): bool {
        $claimData = (new BinaryCodec())->encodeForSigningClaim([
            'channel' => $channelId,
            'amount' => $amountDrops,
        ]);

        return self::keyPairServiceFor($publicKey)->verify($claimData, $signature, $publicKey);
    }

    /**
     * The key pair service a public key belongs to: Ed25519 keys carry the
     * ED prefix, secp256k1 keys start with 02 or 03.
     */
    private static function keyPairServiceFor(string $publicKey): KeyPairServiceInterface
    {
        if (str_starts_with($publicKey, 'ED')) {
            return Ed25519KeyPairService::getInstance();
        }
        if (str_starts_with($publicKey, '0')) {
            return Secp256k1KeyPairService::getInstance();
        }

        throw new Exception('Key Type not recognized');
    }

    /**
     * @psalm-param 1337 $tag
     */
    public function getXAddress(int $tag, bool $isTestnet = false): string
    {
        return CoreUtilities::classicAddressToXAddress($this->classicAddress, $tag, $isTestnet);
    }

    /**
     * Decode a serialized transaction, remove the fields that are added during the signing process,
     * and verify that it matches the transaction prior to signing. This gives the user a sanity check
     * to ensure that what they try to encode matches the message that will be recieved by rippled.
     *
     * @param string $serializedTx A signed and serialized transaction.
     * @param array $tx The transaction prior to signing.
     * @return void
     * @throws ValidationException if the transaction does not have a TxnSignature/Signers property, or if the
     * serialized Transaction desn't match the original transaction.
     * @throws XrplException if the transaction includes an issued currency which is equivalent to XRP ignoring case.
     */
    private function checkTxSerialisation(string $serializedTx, array $tx): void
    {
        $decodedTx = $this->binaryCodec->decode($serializedTx);

        /*
         * And ensure it is equal to the original tx, except:
         * - It must have a TxnSignature or Signers (multisign).
         */
        if (!isset($decodedTx['TxnSignature']) && !isset($decodedTx['Signers'])) {
            throw new ValidationException( 'Serialized transaction must have a TxnSignature or Signers property');
        }

        // - We know that the original tx did not have TxnSignature, so we should delete it:
        unset($decodedTx['TxnSignature']);
        // - We know that the original tx did not have Signers, so if it exists, we should delete it:
        unset($decodedTx['Signers']);

        /*
         * - If SigningPubKey was not in the original tx, then we should delete it.
         *   But if it was in the original tx, then we should ensure that it has not been changed.
         */
        if (!isset($tx['SigningPubKey'])) {
            unset($decodedTx['SigningPubKey']);
        }

        /*
         * - Memos have exclusively hex data which should ignore case.
         *   Since decode goes to upper case, we set all tx memos to be uppercase for the comparison.
         */
        if (isset($tx['Memos'])) {
            $tx['Memos'] = array_map(function (array $memo): array {
                if (isset($memo['Memo']['MemoData'])) {
                    if(!XrplUtilities::isHex($memo['Memo']['MemoData'])) {
                        throw new ValidationException('MemoData field must be a hex value');
                    }
                    $memo['Memo']['MemoData'] = strtoupper((string) $memo['Memo']['MemoData']);
                }

                if (isset($memo['Memo']['MemoType'])) {
                    if(!XrplUtilities::isHex($memo['Memo']['MemoType'])) {
                        throw new ValidationException('MemoType field must be a hex value');
                    }
                    $memo['Memo']['MemoType'] = strtoupper((string) $memo['Memo']['MemoType']);
                }

                if (isset($memo['Memo']['MemoFormat'])) {
                    if(!XrplUtilities::isHex($memo['Memo']['MemoFormat'])) {
                        throw new ValidationException('MemoFormat field must be a hex value');
                    }
                    $memo['Memo']['MemoFormat'] = strtoupper((string) $memo['Memo']['MemoFormat']);
                }

                return $memo;
            }, $tx['Memos']);
        }

        if ($tx['TransactionType'] === 'NFTTokenMint' && isset($tx['URI'])) {
            if(!XrplUtilities::isHex($tx['URI'])) {
                throw new ValidationException('URI must be a hex value');
            }
            $tx['URI'] = strtoupper($tx['URI']);
        }

        foreach ($tx as $key => $value) {
            if (XrplUtilities::isIssuedCurrency($value)) {
                $decodedTxAmount = $decodedTx[$key];
                $decodedTxCurrency = $decodedTxAmount['currency'];
                $txAmount = $value;
                $txCurrency = $txAmount['currency'];

                if (strlen((string) $txCurrency) === XrplUtilities::ISSUED_CURRENCY_SIZE && strtoupper((string) $txCurrency) === 'XRP') {
                    throw new XrplException("Trying to sign an issued currency with a similar standard code to XRP (received '{$txCurrency}'). XRP is not an issued currency.");
                }

                if(strlen((string) $txCurrency) !== strlen((string) $decodedTxCurrency)) {
                    if(strlen((string) $decodedTxCurrency) === XrplUtilities::ISSUED_CURRENCY_SIZE) {
                        $decodedTx[$key]['currency'] = XrplUtilities::isoToHex($decodedTxCurrency);
                    } else {
                        $tx[$key]['currency'] = XrplUtilities::isoToHex($txCurrency);
                    }
                }
            }
        }
    }

    /*

    private function removeTrailingZeros(Transaction|array $transaction): void
    {
        //TODO: Test if this is really necessary. Edge case: Amount value 123.4000
    }
    */

    private function computeSignature(array $tx, string $privateKey, ?string $signAs = null): string
    {
        if($signAs) {
            if (CoreUtilities::isValidXAddress($signAs)) {
                $signAs = CoreUtilities::xAddressToClassicAddress($signAs)['classicAddress'];
            }
            $encodedTx = $this->binaryCodec->encodeForMultisigning($tx, $signAs);
        } else {
            $encodedTx = $this->binaryCodec->encodeForSigning($tx);
        }

        return $this->keyPairService->sign($encodedTx, $privateKey);
    }

    /**
     *
     * @return string
     */
    public function getAddress(): string
    {
        return $this->classicAddress;
    }

    /**
     *
     * @return string
     */
    public function getClassicAddress(): string
    {
        return $this->classicAddress;
    }

    /**
     *
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     *
     * @return string
     */
    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    /**
     * @return string|null
     */
    public function getSeed(): string|null
    {
        return $this->seed;
    }
}