<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use BI\BigInteger;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

class UnsignedInt16 extends UnsignedInt
{
    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): UnsignedInt16
    {
        $fromParser = $parser->readUInt16();
        return new UnsignedInt16(Buffer::from($fromParser));
    }

    public static function fromSerializedJson(string $serializedJson): SerializedType
    {
        // TODO: Implement fromValue() method.
    }

    public function toBytes(): Buffer
    {
        $hexStr = $this->value->toBase(16);
        $uint16HexStr = str_pad($hexStr, 4, "0", STR_PAD_LEFT);

        return Buffer::from($uint16HexStr, 'hex');
    }

    public function toHex(): string
    {
        return strtoupper($this->toBytes()->toString());
    }
}