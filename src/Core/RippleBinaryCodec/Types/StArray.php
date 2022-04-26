<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinarySerializer;

class StArray extends  SerializedType
{
    public const ARRAY_END_MARKER_HEX = "F1";

    public const ARRAY_END_MARKER_NAME = "ArrayEndMarker";

    public function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType
    {
        $bytesArray = Buffer::from([]); // const bytes: Array<Buffer> = []
        $binarySerializer = new BinarySerializer($bytesArray);

        while (!$parser->end()) {
            $fieldInstance = $parser->readField();
            if ($fieldInstance->getName() === self::ARRAY_END_MARKER_NAME) {
                break;
            }

            $fieldValue = $parser->readFieldValue($fieldInstance)->toBytes();
            $binarySerializer->writeFieldAndValue($fieldInstance, $fieldValue);
            $binarySerializer->put(StObject::OBJECT_END_MARKER_HEX);

            //TODO: Handle constants from expression
            $bytesArray[] = Buffer::from([0xe1]); //ARRAY_END_MARKER

            return new StArray(Buffer::concat($bytesArray));

        }
    }

    public function fromValue(SerializedType $value, ?int $number): SerializedType
    {
        // TODO: Implement from() method.
    }
}