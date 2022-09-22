<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use Brick\Math\BigInteger;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleAddressCodec\AddressCodec;
use XRPL_PHP\Core\RippleBinaryCodec\Definitions\Definitions;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinarySerializer;
use XRPL_PHP\Models\Transactions\Address;

class StObject extends SerializedType
{
    //public const OBJECT_END_MARKER = 0xe1;

    public const OBJECT_END_MARKER_HEX = "E1";

    public const OBJECT_END_MARKER_NAME = "ObjectEndMarker";

    public const ST_OBJECT = "STObject";

    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType
    {
        $binarySerializer = new BinarySerializer(Buffer::alloc(0));

        while (!$parser->end()) {
            $field = $parser->readField();
            if ($field->getName() === self::OBJECT_END_MARKER_NAME) {
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

    public static function fromJson(string $serializedJson): SerializedType
    {
        $json = json_decode($serializedJson, true);
        $binarySerializer = new BinarySerializer(Buffer::alloc(0));
        $addressCodec = new AddressCodec();
        $definitions = Definitions::getInstance();

        $isUnlModify = false;

        foreach ($json as $key => $value) {
            $fieldInstance = $definitions->getFieldInstance($key);
            $fieldJson = (is_array($value)) ? json_encode($value) : $value;
            $serializedTypeInstance = SerializedType::getTypeByName($fieldInstance->getType());
            $fieldValue = $serializedTypeInstance::fromJson($fieldJson);
            $binarySerializer->writeFieldAndValue($fieldInstance, $fieldValue);
            //TODO: add filter f.ex. serialize
        }

        if ($fieldInstance->getType() === self::ST_OBJECT) {
            $binarySerializer->put(self::OBJECT_END_MARKER_HEX);
        }

        return new StObject($binarySerializer->getBytes());
    }

    public function toJson(): array|string
    {
        $binaryParser = new BinaryParser($this->bytes->toString());
        $accumulator = [];

        while (!$binaryParser->end()) {
            $fieldInstance = $binaryParser->readField();
            if ($fieldInstance->getName() === self::OBJECT_END_MARKER_NAME) {
                break;
            }

            $node = $binaryParser->readFieldValue($fieldInstance)->toJson();
            if(is_array($node)) {
                $accumulator[$fieldInstance->getName()] = $node;
            } else {
                $mappedNode = Definitions::getInstance()->mapValueToSpecificField($fieldInstance->getName(), $node);
                //$value = hexdec($node);
                $accumulator[$fieldInstance->getName()] = (!empty($mappedNode)) ? $mappedNode : $node; //TODO: was $node, now it's a bit hacky
            }
        }

        return $accumulator;
    }

    private function mapSpecializedValues(string $fieldName, array $fieldNode): array
    {
        //TODO: implement mapSpecializedValues() method
    }
}