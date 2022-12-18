<?php declare(strict_types=1);

namespace XRPL_PHP\Wallet;

use Exception;
use XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use XRPL_PHP\Core\RippleKeyPairs\Ed25519KeyPairService;
use XRPL_PHP\Core\RippleKeyPairs\KeyPair;
use XRPL_PHP\Core\RippleKeyPairs\KeyPairServiceInterface;
use XRPL_PHP\Core\RippleKeyPairs\Secp256k1KeyPairService;
use XRPL_PHP\Core\Utilities;
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
            throw new Exception('Key Type not recognized');
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

    /**
     *
     *
     * @param Transaction|array $transaction
     * @param string|bool $multisign
     * @return array
     * @throws Exception
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
            $txPayload = $transaction->toArray();
        } else {
            $txPayload = $transaction;
        }

        if (isset($txPayload['TxnSignature']) || isset($txPayload['Signers'])) {
            throw new \Exception( 'txJSON must not contain "TxnSignature" or "Signers" properties',);
        }

        $txPayload['SigningPubKey'] = ($multisignAddress) ? '' : $this->publicKey;

        if ($multisignAddress) {
            $signer = [
                'Account' => $multisignAddress,
                'SigningPubKey' => $this->getPublicKey(),
                'TxnSignature' => $this->computeSignature(
                    $txPayload,
                    $this->getPrivateKey(),
                    $multisignAddress
                )
            ];
            $txPayload['Signers'] = [['Signer' => $signer]];
        } else {
            $signature = $this->computeSignature($txPayload, $this->getPrivateKey());
            $txPayload['TxnSignature'] = $signature;
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
    public function verifyTransaction(string $signedTransaction): bool
    {
        $tx = $this->binaryCodec->decode($signedTransaction);
        $messageHex = $this->binaryCodec->encodeForSigning($tx);
        $signature = $tx['TxnSignature'];

        return $this->keyPairService->verify($messageHex, $signature, $this->publicKey);
    }

    public function getXAddress(mixed $tag, bool $isTestnet = false): string
    {
        return Utilities::classicAddressToXAddress($this->classicAddress, $tag, $isTestnet);
    }

    /*
    public function checkSerialisation()
    {
        //TODO: implement function
    }

    private function removeTraiingZeros(Transaction|array $transaction): void
    {
        //TODO: Test if this is really necessary. Edge case: Amount value 123.4000
    }
    */

    private function computeSignature(array $tx, string $privateKey, ?string $signAs = null): string
    {
        if($signAs) {
            if (Utilities::isValidXAddress($signAs)) {
                $signAs = Utilities::xAddressToClassicAddress($signAs)['classicAddress'];
            }
            $encodedTx = $this->binaryCodec->encodeForMultisigning($tx, $signAs);
        } else {
            $encodedTx = $this->binaryCodec->encodeForSigning($tx);
        }

        return $this->keyPairService->sign($encodedTx, $privateKey);
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
}