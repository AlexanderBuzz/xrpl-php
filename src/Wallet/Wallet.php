<?php declare(strict_types=1);

namespace XRPL_PHP\Wallet;

use XRPL_PHP\Core\HashPrefix;
use XRPL_PHP\Core\MathUtilities;
use XRPL_PHP\Core\RippleAddressCodec\AddressCodec;
use XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use XRPL_PHP\Core\RippleKeyPairs\AbstractKeyPairService;
use XRPL_PHP\Core\RippleKeyPairs\Ed25519KeyPairService;
use XRPL_PHP\Core\RippleKeyPairs\KeyPair;
use XRPL_PHP\Core\RippleKeyPairs\KeyPairServiceInterface;
use XRPL_PHP\Core\RippleKeyPairs\Secp256k1KeyPairService;
use XRPL_PHP\Core\Utilities;
use XRPL_PHP\Models\Transactions\Transaction as Transaction;
use XRPL_PHP\Utils\Hashes\HashLedger;

class Wallet {

    public const DEFAULT_ALGORITHM = KeyPair::EDDSA;

    private BinaryCodec $binaryCodec; //TODO static instance?

    private KeyPairServiceInterface $keyPairService;

    private string $publicKey;

    private string $privateKey;

    //private string $address; //TODO: implement correctly

    private string $classicAddress;

    private ?string $seed;

    public function __construct(
        string $publicKey,
        string $privateKey,
        ?string $masterAddress = null,
        ?string $seed = null
    )
    {
        $this->binaryCodec = new BinaryCodec();

        if (str_starts_with($publicKey, 'ED')) {
            $this->keyPairService = Ed25519KeyPairService::getInstance();
        } else if (str_starts_with($publicKey, '0')) {
            $this->keyPairService = Secp256k1KeyPairService::getInstance();
        } else {
            //error
        }

        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;

        if (is_string($masterAddress)) {
            $this->classicAddress = Utilities::ensureClassicAddress($masterAddress);
        } else {
            $this->classicAddress = Utilities::deriveAddress($publicKey);
        }

        $this->seed = $seed;
    }

    public static function generate(string $type = self::DEFAULT_ALGORITHM): Wallet
    {
        $keyPairService = KeyPair::getKeyPairServiceByType($type);
        $seed = $keyPairService->generateSeed();

        return Wallet::fromSeed(
            seed:$seed,
            type: $type
        );
    }

    public static function fromSeed(string $seed, ?string $masterAddress = null, string $type = self::DEFAULT_ALGORITHM): Wallet
    {
        return self::deriveWallet($seed, $masterAddress, $type);
    }

    private static function deriveWallet(string $seed, ?string $masterAddress = null, string $type = self::DEFAULT_ALGORITHM): Wallet
    {
        $keyPairService = KeyPair::getKeyPairServiceByType($type);
        $keyPair = $keyPairService->deriveKeyPair($seed);

        return new Wallet(
            $keyPair->getPublicKey(),
            $keyPair->getPrivateKey(),
            $masterAddress,
            $seed

        );
    }

    /*
    public function checkSerialisation()
    {
        //TODO: implement function
    }

    public function getXAddress()
    {
        //TODO: implement function
    }
    */

    /**
     * No Multisign for now...
     *
     * @param Transaction $transaction
     * @return string[]
     */
    public function sign(Transaction|array $transaction, string|bool $multisign = false): array
    {
        //TODO: remove array as possbile parameter
        $multisignAddress = false;

        if (is_string($multisign) && str_starts_with($multisign, 'X')) {
            $multisignAddress = $multisign;
        } else if ($multisign) {
            $multisignAddress = $this->getClassicAddress();
        }

        //TODO: remove array as possible parameter, use Transaction
        if (!is_array($transaction)) {
            $txPayload = $transaction->getPayload();
        } else {
            $txPayload = $transaction;
        }

        if (isset($txPayload['TxnSignature']) || isset($txPayload['Signers'])) {
            throw new \Exception( 'txJSON must not contain "TxnSignature" or "Signers" properties',);
        }

        $txPayload[Transaction::JSON_PROPERTY_SIGNING_PUBLIC_KEY] = $this->publicKey;

        if ($multisignAddress) {
            // TODO: Implement multisign
        } else {
            $signature = $this->computeSignature($txPayload);
            $txPayload[Transaction::JSON_PROPERTY_TRANSACTION_SIGNATURE] = $signature;
        }

        $serializedTx = $this->binaryCodec->encode($txPayload);
        $hash = HashLedger::hashSignedTx($serializedTx);

        return [
            "tx_blob" => $serializedTx,
            "hash" => $hash,
        ];
    }

    /**
     * Verifies a signed transaction offline.
     *
     * @param string $signedTransaction A signed transaction (hex string of signTransaction result) to be verified offline.
     * @return bool Returns true if a signedTransaction is valid.
     */
    public function verifyTransaction(string $signedTransaction)
    {
        $tx = $this->binaryCodec->decode($signedTransaction);
        $messageHex = $this->binaryCodec->encodeForSigning($tx);
        $signature = $tx['TxnSignature'];
        return $this->keyPairService->verify($messageHex, $signature, $this->publicKey);
    }

    /*
    public function getXAddress(mixed $number, mixed $tag, bool $isTestnet = false): string
    {

    }
    */

    private function computeSignature(array $tx, ?string $signAs = null): string
    {
        if($signAs) {
            if (Utilities::isValidXAddress($signAs)) {
                $signAs = Utilities::xAddressToClassicAddress($signAs)['classicAddress'];
            }
            $encodedTx = $this->binaryCodec->encodeForMultisigning($tx, $signAs);
        } else {
            $encodedTx = $this->binaryCodec->encodeForSigning($tx);
        }

        return $this->keyPairService->sign($encodedTx, $this->privateKey);
    }

    public function getAddress(): string|null
    {
        return $this->classicAddress;
    }

    public function getClassicAddress(): string|null
    {
        return $this->classicAddress;
    }

    /**
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
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

    /*
    private function removeTrailingZeros(Transaction|array $tx): void
    {
        if (
            $tx.TransactionType === 'Payment' &&
            typeof tx.Amount !== 'string' &&
        tx.Amount.value.includes('.') &&
        tx.Amount.value.endsWith('0')
  ) {
        // eslint-disable-next-line no-param-reassign -- Required to update Transaction.Amount.value
        tx.Amount = { ...tx.Amount }
    // eslint-disable-next-line no-param-reassign -- Required to update Transaction.Amount.value
    tx.Amount.value = new BigNumber(tx.Amount.value).toString()
  }
    }
    */

}