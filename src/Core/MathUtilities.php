<?php declare(strict_types=1);

namespace XRPL_PHP\Core;

use Brick\Math\BigDecimal;

class MathUtilities
{
    /*
    public static function getPrecision(BigDecimal $value): int
    {
        k = getPrecision(x.d);
        if (z && x.e + 1 > k) k = x.e + 1;
    }

    public static function getExponent(BigDecimal $value): int
    {
        return static::getPrecision($value) - $value->getScale() -1;
    }
    */

    public static function unsignedRightShift(int $value, int $steps): int
    {
        if ($steps === 0) {
            return $value;
        }

        return ($value >> $steps) & ~(1 << (8 * PHP_INT_SIZE - 1) >> ($steps - 1));
    }
}