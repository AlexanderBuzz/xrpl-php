<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use BI\BigInteger;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

class UnsignedInt64 extends UnsignedInt
{
    public function fromParser(BinaryParser $parser, ?int $lengthHint = null): UnsignedInt64
    {
        $fromParser = $parser->readUInt64();
        return new UnsignedInt64(Buffer::from($fromParser));
    }

    public function fromSerializedJson(string $serializedJson): UnsignedInt64
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

    public function toHex(): string
    {
        return strtoupper($this->toBytes(false)->toString());
    }
}