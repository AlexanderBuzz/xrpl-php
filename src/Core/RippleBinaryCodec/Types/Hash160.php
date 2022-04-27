<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use BI\BigInteger;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

class Hash160 extends Hash
{
    protected static int $width = 20;

    public function __construct(?Buffer $bytes = null)
    {
        parent::__construct($bytes, static::$width);
    }

    public function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType
    {
        return new Hash160($parser->read(static::$width));
    }

    public function fromValue(SerializedType $value, ?int $number): SerializedType
    {
        // TODO: Implement fromValue() method.
    }
}