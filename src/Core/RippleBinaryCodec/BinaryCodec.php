<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XRPL_PHP\Core\RippleBinaryCodec;

use Exception;
use XRPL_PHP\Core\HashPrefix;
use XRPL_PHP\Core\RippleBinaryCodec\Definitions\Definitions;
use XRPL_PHP\Core\RippleBinaryCodec\Types\AccountId;
use XRPL_PHP\Core\RippleBinaryCodec\Types\StObject;

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

        return StObject::fromJson($jsonObject)->toString();
    }

    /**
     * Deserializes a hex-encoded string into an array.
     *
     * @param string $binaryString
     * @return array
     */
    public function decode(string $binaryString): array
    {
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

    /*
    public function encodeForSigningClaim(array $object): string
    {
        assert.ok(typeof json === 'object')
  return signingClaimData(json as ClaimObject)
    .toString('hex')
    .toUpperCase()
    }
    */

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
        return Definitions::getInstance()->getFieldInstance($fieldName)->isSigningField();
    }
}