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

    private string $address;

    private ?string $classicAddress;

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

    public static function fromSeed(string $seed, ?string $masterAddress = null, $type = self::DEFAULT_ALGORITHM): Wallet
    {
        return self::deriveWallet($seed, $masterAddress, $type);
    }

    /*
     *   private static deriveWallet(
    seed: string,
    opts: { masterAddress?: string; algorithm?: ECDSA } = {},
  ): Wallet {
    const { publicKey, privateKey } = deriveKeypair(seed, {
      algorithm: opts.algorithm ?? DEFAULT_ALGORITHM,
    })
    return new Wallet(publicKey, privateKey, {
      seed,
      masterAddress: opts.masterAddress,
    })
  }
     */

    private static function deriveWallet(string $seed, ?string $masterAddress = null, $type = self::DEFAULT_ALGORITHM): Wallet
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

    public function checkSerialisation()
    {

    }

    public function getXAddress()
    {

    }

    /**
     * No Multisign for now...
     *
     * @param Transaction $transaction
     * @return string[]
     */
    public function sign(Transaction $transaction): array
    {
        $multisignAddress = false;

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

    /**
     * Some humble shortcut...
     *
     * @return array
     */
    public function returnHardcodedSignature(): array
    {
        $serializedTx = "120000220000000024019251F2201B0193D2ED6140000000014FB18068400000000000000C7321039543A0D3004CDA0904A09FB3710251C652D69EA338589279BC849D47A7B019A174473045022100DA2A60B79CB19107892F7A73F74A7B5F4C4AFFF77294F8EBFA5A52BF1BA4F27E0220046910CF91CE1324ED108E66251C9B0DCDC7570F6EEA0626127C1495BD96B1078114E2AFBD269D7DA5E2B9931CCBD62FAB5118A366188314F667B0CA50CC7709A220B0561B85E53A48461FA8";
        $hash = "FF8EEB399F00034CC498CFFFB75DCFFCFB6DBEE0D61FE2D36E7CFF9E3B38674E";

        return [
            "tx_blob" => $serializedTx,
            "hash" => $hash,
        ];
    }

    public function verifyTransaction()
    {

    }

    private function computeSignature(array $txPayload): array
    {
        $encoded = $this->encodeForSigning($txPayload);

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

    /**
     * Move to separate package
     *
     * @param array $transactionData
     * @param string $prefix
     * @return mixed
     */
    private function signingData(array $transactionData, string $prefix = HashPrefix::TRANSACTION_SIGN)
    {
        $transactionData['prefix'] = $prefix;
        $transactionData['signingFieldsOnly'] = true;

        //return $this->serializeObject($transactionData, { prefix, signingFieldsOnly: true })
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->classicAddress;
    }

    /**
     * @return string
     */
    public function getClassicAddress(): string
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
     * @return string
     */
    public function getSeed(): string
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

    private function serializeObject()
    {

    }

    private function hashSignedTx(string $serializedTx): string
    {
        return '';
    }


}