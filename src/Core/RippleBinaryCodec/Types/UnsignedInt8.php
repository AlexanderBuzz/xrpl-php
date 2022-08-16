<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use Brick\Math\BigInteger;
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

    public static function fromJson(string|int $serializedJson): SerializedType
    {
        if (is_string($serializedJson)) {
            $serializedJson = (int) json_decode($serializedJson);
        }

        return new UnsignedInt8(Buffer::from(dechex($serializedJson)));
    }

    public function valueOf(): int|string
    {
        return $this->value->toInt();
    }
}