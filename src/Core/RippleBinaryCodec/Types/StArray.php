<?php declare(strict_types=1);

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
            if ($fieldInstance->getType() === self::ARRAY_END_MARKER_NAME) {
                break;
            }

            $associatedValue = $parser->readFieldValue($fieldInstance);

            $binarySerializer->writeFieldAndValue($fieldInstance, $associatedValue);
            $binarySerializer->put(StObject::OBJECT_END_MARKER_HEX);
        }

        $binarySerializer->put(self::ARRAY_END_MARKER_HEX);

        return new StArray($binarySerializer->getBytes());
    }

    public static function fromSerializedJson(string $serializedJson): SerializedType
    {
        // TODO: Implement from() method.
    }

    public function toJson(): array|string
    {
        $binaryParser = new BinaryParser($this->bytes->toString());
        $values = [];

        while (!$binaryParser->end()) {
            $fieldInstance = $binaryParser->readField();
            if ($fieldInstance->getType() === self::ARRAY_END_MARKER_NAME) {
                break;
            }

            $object = StObject::fromParser($binaryParser);
        }

        return parent::toJson();
    }
}