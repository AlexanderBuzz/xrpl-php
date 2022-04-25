<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

class StObject extends SerializedType
{
    static function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType
    {
        /* WIP
        $bytesArray = array(); // const bytes: Array<Buffer> = []

        $OBJECT_END_MARKER_BYTE = Buffer::from([0xe1]);

        while (!$parser->end()) {
            $field = $parser->readField();
            if ($field->getName() === self::OBJECT_END_MARKER) {
                break;
            }

            $associatedValue = $parser->readFieldValue($field);

        }
        */
    }
}