<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

class StArray extends  SerializedType
{
    public const ARRAY_END_MARKER_HEX = "F1";

    public const ARRAY_END_MARKER_NAME = "ArrayEndMarker";

    static function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType
    {
        $bytesArray = array(); // const bytes: Array<Buffer> = []

        $OBJECT_END_MARKER_BYTE = Buffer::from([0xe1]);

        while (!$parser->end()) {
            $field = $parser->readField();
            if ($field->getName() === self::ARRAY_END_MARKER_NAME) {
                break;
            }

            $bytesArray[] = [
                $field->getHeader(),
                $parser->readFieldValue($field)->toBytes(),
                self::OBJECT_END_MARKER
            ];

            //TODO: Handle constants from expression
            $bytesArray[] = Buffer::from([0xe1]); //ARRAY_END_MARKER

            return new StArray(Buffer::concat($bytesArray));

        }
    }

    static function from(SerializedType $value, ?int $number): SerializedType
    {
        // TODO: Implement from() method.
    }
}