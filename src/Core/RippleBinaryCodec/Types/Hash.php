<?php declare(strict_types=1);

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