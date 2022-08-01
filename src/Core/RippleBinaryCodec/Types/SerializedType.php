<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BytesList;

/**
 * JavaScript:
 * https://github.com/XRPLF/xrpl.js/blob/main/packages/ripple-binary-codec/src/types/serialized-type.ts
 *
 * Java:
 * https://github.com/XRPLF/xrpl4j/blob/main/xrpl4j-binary-codec/src/main/java/org/xrpl/xrpl4j/codec/binary/types/SerializedType.java
 */
abstract class SerializedType
{
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
    }

    public function toBytes(): Buffer
    {
        if (!$this->buffer) {
            return Buffer::from([]);
        }
        return $this->buffer;
    }

    public function toHex(): string
    {
        return strtoupper($this->buffer->toString());
    }

    public function toJson(): array|string|int
    {
        return $this->toHex();
    }

    public function toString(): string
    {
        return $this->toHex(); //TODO: check if this applies in every casel
    }

    public function fromHex(string $hex): SerializedType
    {
        $parser = new BinaryParser($hex);
        return $this->fromParser($parser);
    }

    public static function getTypeByName(string $name): SerializedType
    {
        $typeMap = [
            "AccountID" => AccountId::class,
            "Amount" => Amount::class,
            //"Blob" => Blob,
            "Currency" => Currency::class,
            "Hash128" => Hash128::class,
            "Hash160" => Hash160::class,
            "Hash256" => Hash256::class,
            //"PathSet" => PathSet,
            "STArray" => StArray::class,
            "STObject" => StObject::class,
            "UInt8" => UnsignedInt8::class,
            "UInt16" => UnsignedInt16::class,
            "UInt32" => UnsignedInt32::class,
            "UInt64" => UnsignedInt64::class,
            //"Vector256" => Vector256,
        ];

        if (!isset($typeMap[$name])) {
            throw new \Exception("unsupported type " . $name);
        }

        //return class instance
        return new $typeMap[$name]();
    }

    public static function getNameByType(SerializedType $type): string
    {

    }

    abstract function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType;

    abstract function fromSerializedJson(string $serializedJson): SerializedType;

}