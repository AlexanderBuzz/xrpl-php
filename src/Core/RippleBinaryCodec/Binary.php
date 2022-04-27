<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec;

use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;
use XRPL_PHP\Core\RippleBinaryCodec\Types\JsonObject;
use XRPL_PHP\Core\RippleBinaryCodec\Types\StArray;
use XRPL_PHP\Core\RippleBinaryCodec\Types\StObject;

class Binary
{
    public function makeParser(string $bytes): BinaryParser
    {
        return new BinaryParser($bytes);
    }

    public function serializeObject(JsonObject $object, array $options = [])
    {
        [
            'prefix' => $prefix,
            'suffix' => $suffix,
            'signingFieldsOnly' => $signingFieldsOnly //default false!
        ] = $object;

        $bytesList = [];

        if ($prefix) {
            $bytesList[] = $prefix->getHex();
        }


    }

    /**
     *
     *
     * @param BinaryParser $parser
     * @return array
     */
    public function readJson(BinaryParser $parser): array //xrpl.js: JsonObject, defined in serialized-type.js
    {
        $type = new StObject();

        return $parser->readType($type)->toJson(); // currently implementing this
    }

    public function multiSigningData()
    {

    }

    public function signingData()
    {

    }

    public function signingClaimData()
    {

    }

    public function binaryToJson(string $bytes): array
    {
        $parser = $this->makeParser($bytes);

        return $this->readJson($parser);
    }

    public function sha512Half()
    {

    }
}