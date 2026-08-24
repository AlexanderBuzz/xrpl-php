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
use Exception;
use Hardcastle\Buffer\Buffer;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

/**
 * STNumber, the XRPL "Number" type.
 *
 * Always serialized as 12 bytes: a signed 64 bit mantissa followed by a signed
 * 32 bit exponent, both big endian. Used by the Vault and Lending Protocol
 * ledger objects and transactions.
 *
 * JavaScript:
 * https://github.com/XRPLF/xrpl.js/blob/main/packages/ripple-binary-codec/src/types/st-number.ts
 */
class Number extends SerializedType
{
    public const BYTE_LENGTH = 12;

    /** 10^18 */
    public const MIN_MANTISSA = '1000000000000000000';

    /** 10^19 - 1 */
    public const MAX_MANTISSA = '9999999999999999999';

    /** 2^63 - 1 */
    public const MAX_INT64 = '9223372036854775807';

    public const MIN_EXPONENT = -32768;

    public const MAX_EXPONENT = 32768;

    /**
     * Exponent rippled uses to encode a canonical zero.
     */
    public const DEFAULT_VALUE_EXPONENT = -2147483648;

    /**
     * Number of significant decimal digits rippled renders with.
     */
    private const RANGE_LOG = 18;

    /**
     * @param Buffer|null $bytes
     * @throws Exception
     */
    public function __construct(?Buffer $bytes = null)
    {
        if (!$bytes) {
            $bytes = Buffer::alloc(self::BYTE_LENGTH);
        }

        if ($bytes->getLength() !== self::BYTE_LENGTH) {
            throw new Exception("Invalid Number length " . $bytes->getLength());
        }

        parent::__construct($bytes);
    }

    /**
     * Read a Number from a BinaryParser
     *
     * @param BinaryParser $parser
     * @param int|null $lengthHint
     * @return SerializedType
     * @throws Exception
     */
    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType
    {
        return new Number($parser->read(self::BYTE_LENGTH));
    }

    /**
     * Create a Number from a decimal or scientific notation string
     *
     * @param string $serializedJson
     * @return SerializedType
     * @throws Exception
     */
    public static function fromJson(string $serializedJson): SerializedType
    {
        [$mantissa, $exponent] = self::extractNumberParts($serializedJson);
        [$mantissa, $exponent] = self::normalize($mantissa, $exponent);

        return new Number(Buffer::concat([
            self::packSignedInt($mantissa, 8),
            self::packSignedInt(BigInteger::of($exponent), 4)
        ]));
    }

    /**
     * Returns the canonical string representation of this Number
     *
     * @return array|string|int
     * @throws Exception
     */
    public function toJson(): array|string|int
    {
        $mantissa = self::unpackSignedInt($this->bytes->slice(0, 8));
        $exponent = (int)self::unpackSignedInt($this->bytes->slice(8, 12))->toBase(10);

        if ($mantissa->isZero() && $exponent === self::DEFAULT_VALUE_EXPONENT) {
            return '0';
        }

        $isNegative = $mantissa->isNegative();
        $mantissaAbs = $mantissa->abs();

        // Mantissas above 2^63-1 are shrunk by one digit before serialization,
        // restore them so the rendering matches rippled's internal value.
        if (!$mantissaAbs->isZero() && $mantissaAbs->isLessThan(BigInteger::of(self::MIN_MANTISSA))) {
            $mantissaAbs = $mantissaAbs->multipliedBy(10);
            $exponent -= 1;
        }

        $sign = $isNegative ? '-' : '';

        if ($exponent !== 0 &&
            ($exponent < -(self::RANGE_LOG + 10) || $exponent > -(self::RANGE_LOG - 10))
        ) {
            // Scientific notation, with trailing zeros stripped from the mantissa
            while (!$mantissaAbs->isZero()
                && $mantissaAbs->mod(10)->isZero()
                && $exponent < self::MAX_EXPONENT
            ) {
                $mantissaAbs = $mantissaAbs->quotient(10);
                $exponent += 1;
            }

            return $sign . $mantissaAbs->toBase(10) . 'e' . $exponent;
        }

        $padPrefix = self::RANGE_LOG + 12;
        $padSuffix = self::RANGE_LOG + 8;

        $rawValue = str_repeat('0', $padPrefix) . $mantissaAbs->toBase(10) . str_repeat('0', $padSuffix);
        $offset = $exponent + $padPrefix + self::RANGE_LOG + 1;

        $integerPart = ltrim(substr($rawValue, 0, $offset), '0');
        if ($integerPart === '') {
            $integerPart = '0';
        }
        $fractionPart = rtrim(substr($rawValue, $offset), '0');

        return $sign . $integerPart . ($fractionPart !== '' ? '.' . $fractionPart : '');
    }

    /**
     * Split a number string into an (unnormalized) mantissa and exponent
     *
     * @param string $value
     * @return array{0: BigInteger, 1: int}
     * @throws Exception
     */
    private static function extractNumberParts(string $value): array
    {
        $matches = [];
        if (!preg_match('/^([-+]?)([0-9]+)(?:\.([0-9]+))?(?:[eE]([+-]?[0-9]+))?$/', $value, $matches)) {
            throw new Exception("Unable to parse number from string: {$value}");
        }

        $sign = $matches[1];
        $intPart = ltrim($matches[2], '0');
        if ($intPart === '') {
            $intPart = '0';
        }
        $fracPart = $matches[3] ?? '';
        $expPart = $matches[4] ?? '';

        $mantissaStr = $intPart;
        $exponent = 0;

        if ($fracPart !== '') {
            $mantissaStr .= $fracPart;
            $exponent -= strlen($fracPart);
        }
        if ($expPart !== '') {
            $exponent += (int)$expPart;
        }

        while (strlen($mantissaStr) > 1 && str_ends_with($mantissaStr, '0')) {
            $mantissaStr = substr($mantissaStr, 0, -1);
            $exponent += 1;
        }

        $mantissa = BigInteger::of($mantissaStr);
        if ($sign === '-') {
            $mantissa = $mantissa->negated();
        }

        return [$mantissa, $exponent];
    }

    /**
     * Bring mantissa and exponent into the range rippled expects
     *
     * @param BigInteger $mantissa
     * @param int $exponent
     * @return array{0: BigInteger, 1: int}
     * @throws Exception
     */
    private static function normalize(BigInteger $mantissa, int $exponent): array
    {
        $isNegative = $mantissa->isNegative();
        $m = $mantissa->abs();

        if ($m->isZero()) {
            return [BigInteger::zero(), self::DEFAULT_VALUE_EXPONENT];
        }

        $minMantissa = BigInteger::of(self::MIN_MANTISSA);
        $maxMantissa = BigInteger::of(self::MAX_MANTISSA);
        $maxInt64 = BigInteger::of(self::MAX_INT64);

        while ($m->isLessThan($minMantissa) && $exponent > self::MIN_EXPONENT) {
            $exponent -= 1;
            $m = $m->multipliedBy(10);
        }

        $lastDigit = null;

        while ($m->isGreaterThan($maxMantissa)) {
            if ($exponent >= self::MAX_EXPONENT) {
                throw new Exception("Mantissa and exponent are too large");
            }
            $exponent += 1;
            $lastDigit = $m->mod(10);
            $m = $m->quotient(10);
        }

        if ($exponent < self::MIN_EXPONENT || $m->isLessThan($minMantissa)) {
            throw new Exception("Underflow: value too small to represent");
        }

        if ($exponent > self::MAX_EXPONENT) {
            throw new Exception("Exponent overflow: value too large to represent");
        }

        if ($m->isGreaterThan($maxInt64)) {
            if ($exponent >= self::MAX_EXPONENT) {
                throw new Exception("Exponent overflow: value too large to represent");
            }
            $exponent += 1;
            $lastDigit = $m->mod(10);
            $m = $m->quotient(10);
        }

        if ($lastDigit !== null && $lastDigit->isGreaterThanOrEqualTo(5)) {
            $m = $m->plus(1);

            if ($m->isGreaterThan($maxInt64)) {
                if ($exponent >= self::MAX_EXPONENT) {
                    throw new Exception("Exponent overflow: value too large to represent");
                }
                $lastDigit = $m->mod(10);
                $exponent += 1;
                $m = $m->quotient(10);
                if ($lastDigit->isGreaterThanOrEqualTo(5)) {
                    $m = $m->plus(1);
                }
            }
        }

        if ($isNegative) {
            $m = $m->negated();
        }

        return [$m, $exponent];
    }

    /**
     * Encode a signed integer as a big endian two's complement buffer
     *
     * @param BigInteger $value
     * @param int $byteLength
     * @return Buffer
     * @throws Exception
     */
    private static function packSignedInt(BigInteger $value, int $byteLength): Buffer
    {
        if ($value->isNegative()) {
            $value = BigInteger::of(2)->power($byteLength * 8)->plus($value);
        }

        return Buffer::from(
            str_pad($value->toBase(16), $byteLength * 2, '0', STR_PAD_LEFT),
            'hex'
        );
    }

    /**
     * Decode a big endian two's complement buffer into a signed integer
     *
     * @param Buffer $bytes
     * @return BigInteger
     * @throws Exception
     */
    private static function unpackSignedInt(Buffer $bytes): BigInteger
    {
        $byteLength = $bytes->getLength();
        $value = BigInteger::fromBase($bytes->toString(), 16);
        $signBoundary = BigInteger::of(2)->power($byteLength * 8 - 1);

        if ($value->isGreaterThanOrEqualTo($signBoundary)) {
            $value = $value->minus(BigInteger::of(2)->power($byteLength * 8));
        }

        return $value;
    }
}
