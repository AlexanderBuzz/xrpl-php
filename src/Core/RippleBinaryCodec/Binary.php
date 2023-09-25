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

use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;
use XRPL_PHP\Core\RippleBinaryCodec\Types\StObject;

class Binary
{
    public function makeParser(string $bytes): BinaryParser
    {
        return new BinaryParser($bytes);
    }

    /*
    public function serializeObject(string $jsonObject, array $options = [])
    {
        //TODO: This is old an needs to revamped completely
        [
            'prefix' => $prefix,
            'suffix' => $suffix,
            'signingFieldsOnly' => $signingFieldsOnly //default false!
        ] = $options;

        $bytesList = new BytesList();

        if ($prefix) {
            $bytesList->push($prefix);
        }


        $bytesList->push(STObject::fromJson($jsonObject)->toBytes());

        if ($prefix) {
            $bytesList->push($suffix);
        }

      return $bytesList->toBytes();

    }
    */

    /**
     * @param BinaryParser $parser
     */
    public function readJson(BinaryParser $parser): array|int|string //xrpl.js: JsonObject, defined in serialized-type.js
    {
        $type = new StObject();

        return $parser->readType($type)->toJson(); // currently implementing this
    }

    /*
    public function signingData(array $jsonObject): Buffer
    {
        $paddedPrefix = str_pad((string)HashPrefix::TRANSACTION_SIGN, 8, '00', STR_PAD_LEFT);
        return $this->serializeObject(
            $jsonObject,
        )
    }

    public function multiSigningData()
    {
        //TODO: implement function
    }

    public function signingClaimData()
    {
        //TODO: implement function
    }
    */

    public function binaryToJson(string $bytes): array
    {
        $parser = $this->makeParser($bytes);

        return $this->readJson($parser);
    }
}