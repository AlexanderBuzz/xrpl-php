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

class MathUtilities
{
    public static function unsignedRightShift(int $value, int $steps): int
    {
        if ($steps === 0) {
            return $value;
        }

        return ($value >> $steps) & ~(1 << (8 * PHP_INT_SIZE - 1) >> ($steps - 1));
    }

    public static function computePublicKeyHash(Buffer $bytes): Buffer
    {
        $hash256 = hash('sha256', $bytes->toUtf8(), true);
        $hash160 = hash('ripemd160', $hash256, true);

        return Buffer::from($hash160);
    }

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
        $absNumber = $number->abs(); // Get the absolute value
        $integralPart = $absNumber->getIntegralPart();
        $fractionalPart = $absNumber->getFractionalPart();

        if ($include_zeros) {
            $combined = $integralPart . $fractionalPart;
        } else {
            $combined = rtrim($integralPart . $fractionalPart, '0');
        }

        return strlen($combined);

    }

    /**
     * @param BigDecimal $number
     * @return int
     */
    public static function getBigDecimalExponent(BigDecimal $number):int
    {
        if (str_starts_with('0', $number->abs()->getIntegralPart())) {
            $fractional = $number->abs()->getFractionalPart();

            return -1 * (strlen($number->abs()->getFractionalPart()) - strlen(ltrim($fractional, '0')) + 1);
        }

        return strlen($number->abs()->getIntegralPart()) - 1;
    }

    public static function trimAmountZeros(BigDecimal $amount): string
    {
        $ip = $amount->getIntegralPart();
        $fp = $amount->getFractionalPart();

        $trimmed = rtrim($fp, '0');

        // A whole number is rendered without a fractional part, matching how
        // rippled and the reference SDKs serialize token amounts.
        return (strlen($trimmed) > 0) ? $ip . '.' . $trimmed : $ip;
    }
}
