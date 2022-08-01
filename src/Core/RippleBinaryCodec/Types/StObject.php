<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Definitions\Definitions;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinarySerializer;

class StObject extends SerializedType
{
    public const OBJECT_END_MARKER = 0xe1;

    public const OBJECT_END_MARKER_HEX = "E1";

    public const OBJECT_END_MARKER_NAME = "ObjectEndMarker";

    public const ST_OBJECT = "STObject";

    public function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType
    {
        $bytesArray = Buffer::alloc(0); // const bytes: Array<Buffer> = []
        $binarySerializer = new BinarySerializer($bytesArray);

        while (!$parser->end()) {
            $field = $parser->readField();
            if ($field->getType() === self::OBJECT_END_MARKER_NAME) {
                break;
            }

            $associatedValue = $parser->readFieldValue($field);
            $binarySerializer->writeFieldAndValue($field, $associatedValue);

            if ($field->getType() === self::ST_OBJECT) {
                $binarySerializer->put(self::OBJECT_END_MARKER_HEX);
            }
        }

        return new StObject($binarySerializer->getBytes());
    }

    function fromSerializedJson(string $serializedJson): SerializedType
    {
        // TODO: Implement fromValue() method.
    }

    public function toJson(): array|string
    {
        $binaryParser = new BinaryParser($this->bytes->toString());
        $accumulator = [];

        while (!$binaryParser->end()) {
            $fieldInstance = $binaryParser->readField();
            if ($fieldInstance->getType() === self::OBJECT_END_MARKER_NAME) {
                break;
            }

            $node = $binaryParser->readFieldValue($fieldInstance)->toJson();
            $value = hexdec($node);
            $mappedNode = Definitions::getInstance()->mapValueToSpecificField($fieldInstance->getName(), $node);

            $accumulator[$fieldInstance->getName()] = (!empty($mappedNode)) ? $mappedNode : $value; //TODO: was $node, now it's a bit hacky
        }

        return $accumulator;
    }

    private function mapSpecializedValues(string $fieldName, array $fieldNode): array
    {
        //TODO: implement mapSpecializedValues() method
    }
}