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

use BI\BigInteger;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

abstract class Hash extends SerializedType
{
    protected static int $width;

    public function __construct(Buffer $bytes, int $width)
    {
        parent::__construct($bytes);

        if ($bytes->getLength() !== $width) {
            throw new \Exception("Invalid hash length " . $bytes->getLength());
        }

        static::$width = $width;
    }

    public function getWidth(): int
    {
        return static::$width;
    }

}