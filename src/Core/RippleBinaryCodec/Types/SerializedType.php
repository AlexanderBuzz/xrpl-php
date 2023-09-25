<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use Exception;
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
    protected Buffer $bytes;

    public function __construct(?Buffer $bytes = null)
    {
        if (!$bytes) {
            $bytes = Buffer::alloc();
        }
        $this->bytes = $bytes;
    }

    /**
     *
     * @param BytesList $list
     * @return void
     */
    public function toBytesSink(BytesList $list): void
    {
        $list->push($this->bytes);
    }

    /**
     * @return Buffer
     */
    public function toBytes(): Buffer
    {
        // equals "toValue()"
        return $this->bytes;
    }

    /**
     * @return string
     */
    public function toHex(): string
    {
        return strtoupper($this->bytes->toString());
    }

    /**
     * @return array|string|int
     */
    public function toJson(): array|string|int
    {
        return $this->toHex();
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->toHex();
    }

    /**
     *
     * @param string $hex
     * @return SerializedType
     * @throws Exception
     */
    public static function fromHex(string $hex): SerializedType
    {
        $parser = new BinaryParser($hex);
        return static::fromParser($parser);
    }

    /**
     *
     *
     * @param string $name
     * @return SerializedType
     * @throws Exception
     */
    public static function getTypeByName(string $name): SerializedType
    {
        $typeMap = [
            "AccountID" => AccountId::class,
            "Amount" => Amount::class,
            "Blob" => Blob::class,
            "Currency" => Currency::class,
            "Hash128" => Hash128::class,
            "Hash160" => Hash160::class,
            "Hash256" => Hash256::class,
            "PathSet" => PathSet::class,
            "STArray" => StArray::class,
            "STObject" => StObject::class,
            "UInt8" => UnsignedInt8::class,
            "UInt16" => UnsignedInt16::class,
            "UInt32" => UnsignedInt32::class,
            "UInt64" => UnsignedInt64::class,
            "Vector256" => Vector256::class,
        ];

        if (!isset($typeMap[$name])) {
            throw new \Exception("unsupported type " . $name);
        }

        //return class instance
        return new $typeMap[$name]();
    }

    abstract static function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType;

    abstract static function fromJson(string $serializedJson): SerializedType;

}