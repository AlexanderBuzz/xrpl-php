<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec;

use XRPL_PHP\Core\RippleBinaryCodec\Definitions\Definitions;
use XRPL_PHP\Core\RippleBinaryCodec\Types\StObject;

class BinaryCodec extends Binary
{
    const TRANSACTION_SIGN = '0x53545800';

    public function __construct()
    {

    }

    public function encode(string|array $jsonObject): string
    {
        if (is_array($jsonObject)) {
            $jsonObject = json_encode($jsonObject);
        }

        return StObject::fromJson($jsonObject)->toString();
    }

    /**
     * @param string $binaryString
     * @return array
     */
    public function decode(string $binaryString): array
    {
        return $this->binaryToJson($binaryString);
    }

    public function encodeForSigning(string $json): string
    {
        $jsonObject = json_decode($json);
        $filtered = $this->encode($this->removeNonSigningFields($jsonObject));

        return self::TRANSACTION_SIGN . $this->encode($filtered);
    }

    /*
    public function encodeForSigningClaim(array $object): string
    {
        assert.ok(typeof json === 'object')
  return signingClaimData(json as ClaimObject)
    .toString('hex')
    .toUpperCase()
    }

    public function encodeForMultisigning(array $object, string $signer): string
    {
        assert.ok(typeof json === 'object')
  assert.equal(json['SigningPubKey'], '')
  return multiSigningData(json as JsonObject, signer)
    .toString('hex')
    .toUpperCase()
    }
    */

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

    private function removeNonSigningFields(array $jsonObject): array
    {
        foreach ($jsonObject as $fieldName => $value) {
            if (!$this->isSigningField($fieldName)) {
                unset($jsonObject[$fieldName]);
            }
        }

        return $jsonObject;
    }

    private function isSigningField(string $fieldName): bool
    {
        return Definitions::getInstance()->getFieldInstance($fieldName)->isSigningField();
    }
}