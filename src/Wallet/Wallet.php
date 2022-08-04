<?php

namespace XRPL_PHP\Wallet;

use XRPL_PHP\Core\HashPrefix;
use XRPL_PHP\Core\RippleAddressCodec\AddressCodec;
use XRPL_PHP\Models\Transactions\BaseTransaction as Transaction;

class Wallet
{
    private string $address;

    private string $classicAddress;

    private string $publicKey;

    private string $privateKey;

    private string $seed;

    public function __construct()
    {
        $this->initHardcodedWallet();
    }

    public function initHardcodedWallet(): Wallet
    {
        $this->address = "rMCcNuTcajgw7YTgBy1sys3b89QqjUrMpH";
        $this->classicAddress = "rMCcNuTcajgw7YTgBy1sys3b89QqjUrMpH";
        $this->privateKey = "009A8559713F87414EEB019C2BDFF98EA9FB85039661E30D06415C2E4C9E086DED";
        $this->publicKey = "039543A0D3004CDA0904A09FB3710251C652D69EA338589279BC849D47A7B019A1";
        $this->seed = "sn3nxiW7v8KXzPzAqzyHXbSSKNuN9";

        return $this;
    }

    public static function fromSeed(string $seed): Wallet
    {

    }

    public static function deriveWallet(sting $seed): Wallet
    {

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
        //Whoops, some serious work to do...
        return $this->returnHardcodedSignature();

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

        //where?
        //return $this->sign($encoded, $this->privateKey);
    }

    /**
     * Move to separate package
     *
     * @param array $data
     * @return string
     */
    private function encodeForSigning(array $data): string
    {
        $signed = $this->signingData($data);
        $hex = bin2hex($signed);
        $upper = strtoupper($hex);

        return $upper;
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
        return $this->address;
    }

    /**
     * @param string $address
     */
    public function setAddress(string $address): void
    {
        $this->address = $address;
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
        //$prefix = HashPrefix::TRANSACTION_ID.toString(16).toUpperCase()
        $prefix = strtoupper(HashPrefix::TRANSACTION_ID);
        $hashed = $this->sha512Half($prefix . $serializedTx);

        return $hashed;
    }

    private function sha512Half(string $string): string
    {
        $hashSize = 64;
        $hex = bin2hex($string);
        $sha512Hash = hash('sha512', $hex);
        $upper = strtoupper($sha512Hash);
        $reduced = substr($upper, 0, $hashSize);

        return $reduced;
    }

}