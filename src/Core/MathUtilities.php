<?php declare(strict_types=1);

namespace XRPL_PHP\Core;

use Brick\Math\BigDecimal;

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

    public static function deriveSecp256k1PrivateKey(): string
    {
        return '';
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
     * returns the "Stellen / precision"
     * @param BigDecimal $number
     * @return int
     */
    public static function getBigDecimalPrecision(BigDecimal $number): int
    {
        $ip = $number->getIntegralPart();
        $fp = $number->getFractionalPart();

        return strlen($ip) + strlen($fp);
    }

    /**
     * @param BigDecimal $number
     * @return int
     */
    public static function getBigDecimalExponent(BigDecimal $number):int
    {
        return strlen($number->abs()->getIntegralPart()) - 1;
    }
}
