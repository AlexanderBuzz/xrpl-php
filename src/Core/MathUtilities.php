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
        $binaryValue = hex2bin($bytes->toString());
        $hash256 = hash('sha256', $binaryValue, true);
        $hash160 = hash('ripemd160', $hash256, true);
        $hexValue = bin2hex($hash160);

        return Buffer::from($hexValue)->slice(0, 32);
    }

    public static function sha512Half(Buffer|string $input): Buffer
    {
        if(!is_string($input)) {
            $input = $input->toString();
        }

        $binaryValue = hex2bin($input);
        $binaryHash = hash('sha512', $binaryValue, true);
        $hexValue = bin2hex($binaryHash);

        return Buffer::from($hexValue)->slice(0, 32);
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

        return (strlen($trimmed) > 0) ? $ip . '.' . $trimmed  : $ip . '.0';
    }
}
