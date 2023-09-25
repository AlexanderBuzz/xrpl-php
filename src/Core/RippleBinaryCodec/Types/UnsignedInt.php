<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use Brick\Math\BigInteger;
use XRPL_PHP\Core\Buffer;

abstract class UnsignedInt extends SerializedType
{
    protected BigInteger $value;

    /**
     *
     * @param Buffer|null $bytes
     * @throws \Brick\Math\Exception\MathException
     * @throws \Brick\Math\Exception\NumberFormatException
     */
    public function __construct(?Buffer $bytes = null)
    {
        parent::__construct($bytes);

        if ($bytes === null) {
            $this->value = BigInteger::of(0);
        } else {
            $this->value = BigInteger::fromBase($bytes->toString(), 16);
        }
    }

    /**
     *
     *
     * @return Buffer
     */
    public function toBytes(): Buffer
    {
        return $this->bytes;
    }

    /**
     *
     * @return string
     */
    public function toHex(): string
    {
        return strtoupper($this->toBytes()->toString());
    }

    /**
     *
     * @return array|string|int
     */
    public function toJson(): array|string|int
    {
        return $this->valueOf();
    }

    /**
     *
     * @return int|string
     */
    public abstract function valueOf(): int|string;
}