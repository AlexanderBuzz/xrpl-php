<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

class StArray extends  SerializedType
{
    public const ARRAY_END_MARKER_HEX = "F1";

    public const ARRAY_END_MARKER_NAME = "ArrayEndMarker";

    static function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType
    {
        $bytesArray = array(); // const bytes: Array<Buffer> = []

        while (!$parser->end()) {

        }
    }

    static function from(SerializedType $value, ?int $number): SerializedType
    {
        // TODO: Implement from() method.
    }
}