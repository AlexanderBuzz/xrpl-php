<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinarySerializer;

class StArray extends SerializedType
{
    public const ARRAY_END_MARKER = 0xf1;

    public const ARRAY_END_MARKER_HEX = "F1";

    public const ARRAY_END_MARKER_NAME = "ArrayEndMarker";

    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType
    {
        $bytesArray = Buffer::alloc(0); // const bytes: Array<Buffer> = []
        $binarySerializer = new BinarySerializer($bytesArray);

        while (!$parser->end()) {
            $fieldInstance = $parser->readField();
            if ($fieldInstance->getName() === self::ARRAY_END_MARKER_NAME) {
                break;
            }

            $associatedValue = $parser->readFieldValue($fieldInstance);

            $binarySerializer->writeFieldAndValue($fieldInstance, $associatedValue);
            $binarySerializer->put(StObject::OBJECT_END_MARKER_HEX);
        }

        $binarySerializer->put(self::ARRAY_END_MARKER_HEX);

        return new StArray($binarySerializer->getBytes());
    }

    public static function fromJson(string $serializedJson): SerializedType
    {
        $json = json_decode($serializedJson);
        $bytes = Buffer::alloc(0);

        foreach($json as $item) {
            $object = StObject::fromJson(json_encode($item));
            $bytes->appendBuffer($object->toBytes());
        }

        $bytes->appendHex(self::ARRAY_END_MARKER_HEX);

        return new StArray($bytes);
    }

    public function toJson(): array|string
    {
        $binaryParser = new BinaryParser($this->bytes->toString());
        $array = [];

        while (!$binaryParser->end()) {
            $fieldInstance = $binaryParser->readField();
            if ($fieldInstance->getName() === self::ARRAY_END_MARKER_NAME) {
                break;
            }

            $object = StObject::fromParser($binaryParser);
            $array[] = [$fieldInstance->getName() => $object->toJson()]; //TODO:
        }

        return $array;
    }
}