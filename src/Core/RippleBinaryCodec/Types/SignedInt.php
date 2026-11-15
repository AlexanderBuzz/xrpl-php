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

use Hardcastle\XRPL_PHP\Core\MathUtilities;
use Brick\Math\BigInteger;
use Hardcastle\Buffer\Buffer;

/**
 * Base class of the signed integer types, stored in two's complement.
 */
abstract class SignedInt extends SerializedType
{
    protected BigInteger $value;

    public function __construct(?Buffer $bytes = null)
    {
        parent::__construct($bytes);

        if ($bytes === null) {
            $this->value = BigInteger::of(0);
        } else {
            $this->value = $this->fromTwoComplement($bytes);
        }
    }

    public function toBytes(): Buffer
    {
        return $this->toTwoComplement($this->value, static::WIDTH);
    }

    public function toHex(): string
    {
        return strtoupper($this->toBytes()->toString());
    }

    public function toJson(): int|string
    {
        return $this->valueOf();
    }

    abstract public function valueOf(): int|string;

    protected function fromTwoComplement(Buffer $bytes): BigInteger
    {
        $hex = $bytes->toString();
        $val = MathUtilities::bigIntegerFromHex($hex);
        $bitLength = $bytes->getLength() * 8;
        $maxVal = MathUtilities::powerOfTwo($bitLength);
        $midVal = MathUtilities::powerOfTwo($bitLength - 1);

        if ($val->isGreaterThanOrEqualTo($midVal)) {
            return $val->minus($maxVal);
        }

        return $val;
    }

    protected function toTwoComplement(BigInteger $value, int $width): Buffer
    {
        $bitLength = $width * 8;
        $maxVal = MathUtilities::powerOfTwo($bitLength);

        if ($value->isNegative()) {
            $value = $maxVal->plus($value);
        }

        $hex = $value->toBase(16);
        $hex = str_pad($hex, $width * 2, "0", STR_PAD_LEFT);

        return Buffer::from($hex, 'hex');
    }
}
