<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec;

class BinaryCodec extends Binary
{

    public function __construct()
    {

    }

    public function encode(array $object): string
    {
        //assert.ok(typeof json === 'object')

    }

    /**
     * @param string $binaryString
     * @return array
     */
    public function decode(string $binaryString): array
    {
        //assert.ok(typeof binary === 'string', 'binary must be a hex string')
        //hex string expected
        return $this->binaryToJson($binaryString);
    }

    public function encodeForSigning(array $object): string
    {

    }

    public function encodeForSigningClaim(array $object): string
    {

    }

    public function encodeForMultisigning(array $object, string $signer): string
    {

    }

    public function encodeQuality(string $value): string
    {

    }

    public function decodeQuality(string $value): string
    {

    }

    //import { decodeLedgerData } from './ledger-hashes'
    public function decodeLedgerData()
    {

    }
}