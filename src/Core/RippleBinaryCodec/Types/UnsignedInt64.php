<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use Brick\Math\BigInteger;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

class UnsignedInt64 extends UnsignedInt
{
    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): UnsignedInt64
    {
        $fromParser = $parser->readUInt64()->toBase(16);
        return new UnsignedInt64(Buffer::from($fromParser, 'hex'));
    }

    public static function fromJson(string $serializedJson): UnsignedInt64
    {
        //TODO: WIP
        return new UnsignedInt64();
    }

    public function toBytes(): Buffer
    {
        $hexStr = $this->value->toBase(16);
        $uint64HexStr = str_pad($hexStr, 16, "0", STR_PAD_LEFT);

        return Buffer::from($uint64HexStr, 'hex');
    }

    public function valueOf(): int|string
    {
        return $this->value->toBase(10);
    }
}