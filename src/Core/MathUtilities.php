<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Core;

use Brick\Math\BigDecimal;
use Hardcastle\Buffer\Buffer;

/**
 * Decimal and hashing helpers.
 *
 * The decimal functions exist because token amounts carry more precision than
 * a PHP float can hold, so everything goes through brick/math.
 */
class MathUtilities
{
    /**
     * A right shift that does not carry the sign, the way JavaScript's >>> works.
     */
    public static function unsignedRightShift(int $value, int $steps): int
    {
        if ($steps === 0) {
            return $value;
        }

        return ($value >> $steps) & ~(1 << (8 * PHP_INT_SIZE - 1) >> ($steps - 1));
    }

    /**
     * The account id of a public key: RIPEMD160 over its SHA256.
     */
    public static function computePublicKeyHash(Buffer $bytes): Buffer
    {
        $hash256 = hash('sha256', $bytes->toUtf8(), true);
        $hash160 = hash('ripemd160', $hash256, true);

        return Buffer::from($hash160);
    }

    /**
     * The first half of a SHA512, which is how the ledger builds its hashes.
     */
    public static function sha512Half(Buffer|string $input): Buffer
    {
        if ($input instanceof Buffer) {
            $input = $input->toUtf8();
        } else if (preg_match('/^[0-9a-fA-F]+$/', $input)) {
            $input = hex2bin($input);
        }

        $binaryHash = hash('sha512', $input, true);

        return Buffer::from(substr($binaryHash, 0, 32));
    }

    /**
     * Returns the number of significant digits of the value of this Decimal.
     *
     * If include_zeros is true or 1 then any trailing zeros of the integer part of a number are counted as significant digits, otherwise they are not.
     *
     * @param BigDecimal $number
     * @param bool $include_zeros
     * @return int
     */
    public static function getBigDecimalPrecision(BigDecimal $number, bool $include_zeros = false): int
    {
        [$integralPart, $fractionalPart] = self::splitDecimal($number->abs());

        $combined = $integralPart . $fractionalPart;
        if (!$include_zeros) {
            $combined = rtrim($combined, '0');
        }

        return strlen($combined);
    }

    /**
     * The power of ten of the most significant digit.
     *
     * @param BigDecimal $number
     * @return int
     */
    public static function getBigDecimalExponent(BigDecimal $number): int
    {
        [$integralPart, $fractionalPart] = self::splitDecimal($number->abs());

        // Below one the exponent is negative and counts the leading zeros of
        // the fractional part.
        if ($integralPart === '0') {
            return -1 * (strlen($fractionalPart) - strlen(ltrim($fractionalPart, '0')) + 1);
        }

        return strlen($integralPart) - 1;
    }

    /**
     * Render a token amount without trailing zeros, and without a fractional part
     * when there is none left.
     */
    public static function trimAmountZeros(BigDecimal $amount): string
    {
        [$integralPart, $fractionalPart] = self::splitDecimal($amount);

        $trimmed = rtrim($fractionalPart, '0');

        // A whole number is rendered without a fractional part, matching how
        // rippled and the reference SDKs serialize token amounts.
        return (strlen($trimmed) > 0) ? $integralPart . '.' . $trimmed : $integralPart;
    }

    /**
     * Split a decimal into its integral and fractional digits.
     *
     * This replaces BigDecimal::getIntegralPart() and getFractionalPart(),
     * which brick/math 0.15 removes and 0.16 reintroduces with a different
     * meaning. The string form of a BigDecimal is exactly those two parts
     * joined by a dot, so the split reproduces them including the sign on the
     * integral part.
     *
     * @param BigDecimal $number
     * @return array{0: string, 1: string}
     */
    private static function splitDecimal(BigDecimal $number): array
    {
        $decimal = (string)$number;
        $separator = strpos($decimal, '.');

        return ($separator === false)
            ? [$decimal, '']
            : [substr($decimal, 0, $separator), substr($decimal, $separator + 1)];
    }
}
