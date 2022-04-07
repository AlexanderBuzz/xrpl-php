<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BytesList;

class SerializedType
{
    private Buffer $buffer;

    public function __construct(?Buffer $bytes)
    {
        if (!$bytes) {
            $bytes = Buffer::alloc(0);
        }
        $this->buffer = $bytes;
    }

    public static function fromParser(BinaryParser $parser, ?int $hint): SerializedType
    {
        //Seems to be "work in progress", is not implemented
    }

    public static function from(SerializedType $value, ?int $number): SerializedType
    {
        //Seems to be "work in progress", is not implemented
    }

    public function toByteSink(BytesList $list): void
    {
        $list->push($this->buffer);
        //do we need a return?
    }

    public function toHex(): string
    {
        return strtoupper($this->buffer->toString());
    }

    public function toBytes(): Buffer
    {
        //skipped check, $this->bytes is supposedly never NULL
        return $this->buffer;
    }

    public function toJson(): array|string
    {
        return $this->toHex();
    }

    public function toString(): string
    {
        return $this->toHex();
    }

}