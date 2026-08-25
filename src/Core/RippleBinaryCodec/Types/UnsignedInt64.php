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

use Brick\Math\BigInteger;
use Hardcastle\Buffer\Buffer;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

/**
 * An unsigned 64 bit integer.
 *
 * Rendered as a 16 character hex string in JSON, unlike the smaller widths
 * which are numbers. The MPToken amount fields are the exception and use base
 * 10; see BASE_10_FIELDS.
 */
class UnsignedInt64 extends UnsignedInt
{
    public const WIDTH = 8;

    /**
     * UInt64 is represented as a 16 character hex string in JSON, except for
     * these MPToken amount fields, which rippled renders in base 10.
     */
    public const BASE_10_FIELDS = [
        'MaximumAmount',
        'OutstandingAmount',
        'MPTAmount',
        'LockedAmount',
    ];

    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): UnsignedInt64
    {
        $hexValue = $parser->readUInt64()->toString();
        return new UnsignedInt64(Buffer::from($hexValue, 'hex'));
    }

    /**
     * Build a UInt64 from its JSON representation, which is a hex string
     *
     * @param string $serializedJson
     * @return UnsignedInt64
     * @throws \Exception
     */
    public static function fromJson(string $serializedJson): UnsignedInt64
    {
        if (!preg_match('/^[a-fA-F0-9]{1,16}$/', $serializedJson)) {
            throw new \Exception("{$serializedJson} is not a valid hex-string");
        }

        return new UnsignedInt64(Buffer::from(str_pad($serializedJson, 16, '0', STR_PAD_LEFT), 'hex'));
    }

    /**
     * Build a UInt64 from a base 10 string, used for the MPToken amount fields
     *
     * @param string $value
     * @return UnsignedInt64
     * @throws \Exception
     */
    public static function fromBase10(string $value): UnsignedInt64
    {
        if (!preg_match('/^[0-9]{1,20}$/', $value)) {
            throw new \Exception("{$value} is not a valid base 10 string");
        }

        $bigInteger = BigInteger::fromBase($value, 10);

        return new UnsignedInt64(Buffer::from(str_pad($bigInteger->toBase(16), 16, '0', STR_PAD_LEFT), 'hex'));
    }

    /**
     * Whether the given field is serialized as a base 10 string in JSON
     *
     * @param string $fieldName
     * @return bool
     */
    public static function isBase10Field(string $fieldName): bool
    {
        return in_array($fieldName, self::BASE_10_FIELDS, true);
    }

    public function toBytes(): Buffer
    {
        $hexStr = $this->value->toBase(16);
        $uint64HexStr = str_pad($hexStr, 16, "0", STR_PAD_LEFT);

        return Buffer::from($uint64HexStr, 'hex');
    }

    /**
     * The JSON representation of a UInt64 is a 16 character hex string
     *
     * @return array|string|int
     */
    public function toJson(): array|string|int
    {
        return $this->toHex();
    }

    /**
     * The base 10 representation, used for the MPToken amount fields
     *
     * @return string
     */
    public function toBase10(): string
    {
        return $this->value->toBase(10);
    }

    public function valueOf(): int|string
    {
        return $this->value->toBase(10);
    }
}