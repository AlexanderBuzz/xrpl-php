<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use Brick\Math\BigInteger;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

class UnsignedInt32 extends UnsignedInt
{
    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): UnsignedInt32
    {
        $fromParser = $parser->readUInt32();
        return new UnsignedInt32(Buffer::from($fromParser));
    }

    public static function fromJson(string|int $serializedJson): SerializedType
    {
        if (is_string($serializedJson)) {
            $serializedJson = (int) json_decode($serializedJson);
        }

        return new UnsignedInt32(Buffer::from(dechex($serializedJson)));
    }

    public function toBytes(): Buffer
    {
        $hexStr = $this->value->toBase(16);
        $uint32HexStr = str_pad($hexStr, 8, "0", STR_PAD_LEFT);

        return Buffer::from($uint32HexStr, 'hex');
    }

    public function valueOf(): int|string
    {
        return $this->value->toInt();
    }
}