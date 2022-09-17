<?php declare(strict_types=1);

namespace XRPL_PHP\Wallet;

use XRPL_PHP\Core\HashPrefix;
use XRPL_PHP\Core\RippleAddressCodec\AddressCodec;
use XRPL_PHP\Core\RippleKeyPairs\KeyPair;
use XRPL_PHP\Core\Utilities;
use XRPL_PHP\Models\Transactions\BaseTransaction as Transaction;

class Wallet {

    public const DEFAULT_ALGORITHM = KeyPair::EDDSA;

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
        $kps = KeyPair::getKeyPairServiceByType($type);
        $seed = $kps->generateSeed();

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
        $kps = KeyPair::getKeyPairServiceByType($type);
        $keyPair = $kps->deriveKeyPair($seed);

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
    public function sign(Transaction $transaction, string|bool $multisign = false): array
    {
        $multisignAddress = false;

        if (is_string($multisign) && str_starts_with($multisign, 'X')) {
            $multisignAddress = $multisign;
        } else if ($multisign) {
            $multisignAddress = $this->getClassicAddress();
        }

        $txPayload = $transaction->getPayload();

        $txPayload[Transaction::JSON_PROPERTY_SIGNING_PUBLIC_KEY] = $this->publicKey;

        if ($multisignAddress) {

        } else {
            $txPayload[Transaction::JSON_PROPERTY_TRANSACTION_SIGNATURE] = $this->computeSignature($txPayload);
        }

        $serializedTx = json_encode($txPayload);
        $hash = $this->hashSignedTx($serializedTx);

        return [
            "tx_blob" => $serializedTx,
            "hash" => $hash,
        ];
    }

    /*
    public function verifyTransaction()
    {
        //TODO: implement function
    }
    */

    private function computeSignature(array $txPayload): array
    {
        //TODO: implement function
        return [];
    }

    /**
     * Move to separate package
     *
     * @param array $data
     * @return string
     */
    private function encodeForSigning(array $data): string
    {
        /*
        $signed = $this->signingData($data);
        $hex = bin2hex($signed);
        $upper = strtoupper($hex);

        return $upper;
        */

        return '';
    }

    /*
    private function signingData(array $transactionData, string $prefix = HashPrefix::TRANSACTION_SIGN): string
    {
        $transactionData['prefix'] = $prefix;
        $transactionData['signingFieldsOnly'] = true;

        //return $this->serializeObject($transactionData, { prefix, signingFieldsOnly: true })
        //TODO: implement function
    }
    */

    public function getAddress(): string|null
    {
        return $this->classicAddress;
    }

    public function getClassicAddress(): string|null
    {
        return $this->classicAddress;
    }

    /**
     * @param string $classicAddress
     */
    public function setClassicAddress(string $classicAddress): void
    {
        $this->classicAddress = $classicAddress;
    }

    /**
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * @param string $publicKey
     */
    public function setPublicKey(string $publicKey): void
    {
        $this->publicKey = $publicKey;
    }

    /**
     * @return string
     */
    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    /**
     * @param string $privateKey
     */
    public function setPrivateKey(string $privateKey): void
    {
        $this->privateKey = $privateKey;
    }

    /**
     * @return string|null
     */
    public function getSeed(): string|null
    {
        return $this->seed;
    }

    /**
     * @param string $seed
     */
    public function setSeed(string $seed): void
    {
        $this->seed = $seed;
    }

    /*
    private function serializeObject(): string
    {
        //TODO: implement function
    }
    */

    private function hashSignedTx(string $serializedTx): string
    {
        //TODO: implement function
        return '';
    }



}