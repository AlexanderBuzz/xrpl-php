<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types;

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
        $val = BigInteger::fromBase($hex, 16);
        $bitLength = $bytes->getLength() * 8;
        $maxVal = BigInteger::of(2)->power($bitLength);
        $midVal = BigInteger::of(2)->power($bitLength - 1);

        if ($val->isGreaterThanOrEqualTo($midVal)) {
            return $val->minus($maxVal);
        }

        return $val;
    }

    protected function toTwoComplement(BigInteger $value, int $width): Buffer
    {
        $bitLength = $width * 8;
        $maxVal = BigInteger::of(2)->power($bitLength);

        if ($value->isNegative()) {
            $value = $maxVal->plus($value);
        }

        $hex = $value->toBase(16);
        $hex = str_pad($hex, $width * 2, "0", STR_PAD_LEFT);

        return Buffer::from($hex, 'hex');
    }
}
