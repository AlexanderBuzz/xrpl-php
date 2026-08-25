<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types;

use Exception;
use Hardcastle\Buffer\Buffer;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Serdes\BytesList;

/**
 * Base class of every type the binary codec knows.
 *
 * A serialized type is a wrapper around the bytes of one field as they appear
 * on the wire. Every subclass implements the same four operations, so the rest
 * of the codec can treat them interchangeably:
 *
 *   fromParser()  reads an instance out of a byte stream, advancing the parser
 *                 past it. Variable length fields receive the length that the
 *                 field header announced as $lengthHint.
 *   fromJson()    builds an instance from the JSON form rippled uses for that
 *                 field - usually a string, an array for the composite types.
 *   toJson()      the reverse, and the shape rippled expects to receive.
 *   toBytes()     the raw bytes, which is what actually gets signed.
 *
 * fromJson() and toJson() are not symmetric with the PHP type system: a
 * Hash256 takes and returns a hex string, an Amount takes and returns either a
 * string of drops or an array, and STObject nests. What they are symmetric in
 * is the round trip - decode(encode($x)) has to equal $x.
 *
 * Only STObject and STArray resolve field names, so only those two need to
 * know which network's definitions apply; the rest serialize their own bytes
 * and are network agnostic.
 *
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
     * Append these bytes to a list being assembled.
     *
     * @param BytesList $list
     * @return void
     */
    public function toBytesSink(BytesList $list): void
    {
        $list->push($this->bytes);
    }

    /**
     * The raw bytes of this value, as they appear on the wire.
     *
     * @return Buffer
     */
    public function toBytes(): Buffer
    {
        return $this->bytes;
    }

    /**
     * The bytes as an uppercase hex string.
     *
     * @return string
     */
    public function toHex(): string
    {
        return strtoupper($this->bytes->toString());
    }

    /**
     * The JSON form rippled uses for this field.
     *
     * Types that carry no structure of their own fall back to hex, which is
     * how rippled renders them too.
     *
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
     * Read an instance out of a hex string.
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
     * The class that handles a type named in definitions.json.
     *
     * Returns an empty instance, which the codec then uses as a factory - the
     * static fromParser() and fromJson() are reached through it.
     *
     * @param string $name A key of the TYPES section of definitions.json
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
            "Hash192" => Hash192::class,
            "Hash256" => Hash256::class,
            "Hash384" => UnsignedInt384::class,
            "Hash512" => UnsignedInt512::class,
            "Issue" => Issue::class,
            "Number" => Number::class,
            "Path" => Path::class,
            "PathSet" => PathSet::class,
            "PathStep" => PathStep::class,
            "STArray" => StArray::class,
            "STObject" => StObject::class,
            "UInt8" => UnsignedInt8::class,
            "UInt16" => UnsignedInt16::class,
            "UInt32" => UnsignedInt32::class,
            "UInt64" => UnsignedInt64::class,
            "UInt96" => UnsignedInt96::class,
            "UInt192" => UnsignedInt192::class,
            "UInt384" => UnsignedInt384::class,
            "UInt512" => UnsignedInt512::class,
            "Int32" => SignedInt32::class,
            "Int64" => SignedInt64::class,
            "Vector256" => Vector256::class,
            // "XChainBridge" is the spelling used by definitions.json, the
            // lowercase variant is kept for backwards compatibility.
            "XChainBridge" => XchainBridge::class,
            "XchainBridge" => XchainBridge::class
        ];

        if (!isset($typeMap[$name])) {
            throw new \Exception("unsupported type " . $name);
        }

        //return class instance
        return new $typeMap[$name]();
    }

    /**
     * Read an instance from a byte stream, advancing the parser past it.
     *
     * @param BinaryParser $parser
     * @param int|null $lengthHint Byte count for variable length fields
     * @return SerializedType
     */
    abstract static function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType;

    /**
     * Build an instance from the JSON form rippled uses for this field.
     *
     * @param string $serializedJson A scalar, or a JSON encoded array for the composite types
     * @return SerializedType
     */
    abstract static function fromJson(string $serializedJson): SerializedType;

}
