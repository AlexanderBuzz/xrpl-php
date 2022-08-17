<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use Brick\Math\BigInteger;
use XRPL_PHP\Core\Buffer;

abstract class UnsignedInt extends SerializedType
{
    protected BigInteger $value;

    public function __construct(?Buffer $bytes = null)
    {
        parent::__construct($bytes);

        if ($bytes === null) {
            $this->value = BigInteger::of(0);
        } else {
            $this->value = BigInteger::fromBase($bytes->toString(), 16);
        }
    }

    public function toBytes(): Buffer
    {
        return $this->bytes;
    }

    public function toHex(): string
    {
        return strtoupper($this->toBytes()->toString());
    }

    public abstract function valueOf(): int|string;
}