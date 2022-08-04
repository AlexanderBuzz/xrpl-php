<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use BI\BigInteger;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

class UnsignedInt8 extends UnsignedInt
{
    const WIDTH = 32 / 8; //4

    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): UnsignedInt8
    {
        $fromParser = $parser->readUInt8();
        return new UnsignedInt8(Buffer::from($fromParser));
    }

    public static function fromSerializedJson(string $serializedJson): SerializedType
    {
        // TODO: Implement fromValue() method.
    }
}