<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BytesList;

abstract class SerializedType
{
    public const OBJECT_END_MARKER = "ObjectEndMarker";

    protected Buffer $buffer;

    public function __construct(?Buffer $bytes = null)
    {
        if (!$bytes) {
            $bytes = Buffer::alloc(0);
        }
        $this->buffer = $bytes;
    }

    public function toByteSink(BytesList $list): void
    {
        $list->push($this->buffer);
        //do we need a return?
    }

    public function fromHex(string $hex): SerializedType
    {
        $parser = new BinaryParser($hex);
        return $this->fromParser($parser);
    }

    public function toHex(): string
    {
        return strtoupper($this->buffer->toString());
    }

    public function toBytes(): Buffer
    {
        if (!$this->buffer) {
            return Buffer::from([]);
        }
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

    public static function getTypeByName(string $name): SerializedType
    {
        //TODO: get class directly

        $typeMap = [
            "AccountID" => AccountId::class,
            //"Amount" => Amount,
            //"Blob" => Blob,
            //"Currency" => Currency,
            //"Hash128" => Hash128,
            //"Hash160" => Hash160,
            //"Hash256" => Hash256,
            //"PathSet" => PathSet,
            "STArray" => StArray::class,
            //"STObject" => SerializedDict,
            "UInt8" => UnsignedInt8::class,
            "UInt16" => UnsignedInt16::class,
            "UInt32" => UnsignedInt32::class,
            "UInt64" => UnsignedInt64::class,
            //"Vector256" => Vector256,
        ];

        if (!isset($typeMap[$name])) {
            throw new \Exception("unsupported type " . $name);
        }

        return new $typeMap[$name]();
    }

    public static function getNameByType(SerializedType $type): string
    {

    }

    abstract function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType;

    abstract function fromValue(SerializedType $value, ?int $number): SerializedType;

}