<?php declare(strict_types=1);

namespace XRPL_PHP\Wallet;

use Exception;
use XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use XRPL_PHP\Core\RippleKeyPairs\Ed25519KeyPairService;
use XRPL_PHP\Core\RippleKeyPairs\KeyPair;
use XRPL_PHP\Core\RippleKeyPairs\KeyPairServiceInterface;
use XRPL_PHP\Core\RippleKeyPairs\Secp256k1KeyPairService;
use XRPL_PHP\Core\CoreUtilities as CoreUtilities;
use XRPL_PHP\Utils\Utilities as XrplUtilities;
use XRPL_PHP\Exceptions\ValidationException;
use XRPL_PHP\Exceptions\XrplException;
use XRPL_PHP\Models\Transaction\TransactionTypes\BaseTransaction as Transaction;
use XRPL_PHP\Utils\Hashes\HashLedger;

class Wallet
{

    public const DEFAULT_ALGORITHM = KeyPair::EDDSA;

    private BinaryCodec $binaryCodec;

    private KeyPairServiceInterface $keyPairService;

    private string $publicKey;

    private string $privateKey;

    private string $classicAddress;

    private ?string $seed;

    public function __construct(
        string $publicKey,
        string $privateKey,
        string $seed,
        ?string $masterAddress = null,
    )
    {
        $this->binaryCodec = new BinaryCodec();

        if (str_starts_with($publicKey, 'ED')) {
            $this->keyPairService = Ed25519KeyPairService::getInstance();
        } else if (str_starts_with($publicKey, '0')) {
            $this->keyPairService = Secp256k1KeyPairService::getInstance();
        } else {
            throw new Exception('Key Type not recognized');
        }

        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
        $this->seed = $seed;

        if (is_string($masterAddress)) {
            $this->classicAddress = CoreUtilities::ensureClassicAddress($masterAddress);
        } else {
            $this->classicAddress = CoreUtilities::deriveAddress($publicKey);
        }
    }

    public static function generate(string $type = self::DEFAULT_ALGORITHM): Wallet
    {
        $keyPairService = KeyPair::getKeyPairServiceByType($type);
        $seed = $keyPairService->generateSeed();

        return Wallet::fromSeed($seed);
    }

    public static function fromSeed(string $seed): Wallet
    {
        return self::deriveWallet($seed);
    }

    /**
     *
     *
     * @param string $seed
     * @return Wallet
     * @throws Exception
     */
    private static function deriveWallet(string $seed): Wallet
    {
        $decoded = CoreUtilities::decodeSeed($seed);
        $keyPairService = KeyPair::getKeyPairServiceByType($decoded['type']);
        $keyPair = $keyPairService->deriveKeyPair($seed);

        return new Wallet(
            $keyPair->getPublicKey(),
            $keyPair->getPrivateKey(),
            $seed
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

        $hash = HashLedger::hashSignedTx($serializedTx);

        return [
            "tx_blob" => $serializedTx,
            "hash" => $hash,
        ];
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
                    $memo['Memo']['MemoData'] = strtoupper($memo['Memo']['MemoData']);
                }

                if (isset($memo['Memo']['MemoType'])) {
                    if(!XrplUtilities::isHex($memo['Memo']['MemoType'])) {
                        throw new ValidationException('MemoType field must be a hex value');
                    }
                    $memo['Memo']['MemoType'] = strtoupper($memo['Memo']['MemoType']);
                }

                if (isset($memo['Memo']['MemoFormat'])) {
                    if(!XrplUtilities::isHex($memo['Memo']['MemoFormat'])) {
                        throw new ValidationException('MemoFormat field must be a hex value');
                    }
                    $memo['Memo']['MemoFormat'] = strtoupper($memo['Memo']['MemoFormat']);
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

                if (strlen($txCurrency) === XrplUtilities::ISSUED_CURRENCY_SIZE && strtoupper($txCurrency) === 'XRP') {
                    throw new XrplException("Trying to sign an issued currency with a similar standard code to XRP (received '{$txCurrency}'). XRP is not an issued currency.");
                }

                if(strlen($txCurrency) !== strlen($decodedTxCurrency)) {
                    if(strlen($decodedTxCurrency) === XrplUtilities::ISSUED_CURRENCY_SIZE) {
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