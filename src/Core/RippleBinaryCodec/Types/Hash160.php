<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use BI\BigInteger;
use phpDocumentor\Reflection\DocBlock\StandardTagFactory;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

class Hash160 extends Hash
{
    public static int $width = 20;

    public function __construct(?Buffer $bytes = null)
    {
        if (is_null($bytes)) {
            $bytes = Buffer::alloc(static::$width);
        }

        parent::__construct($bytes, static::$width);
    }

    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType
    {
        return new Hash160($parser->read(static::$width));
    }

    public static function fromSerializedJson(string $serializedJson): SerializedType
    {
        return new Hash160(Buffer::from($serializedJson));
    }
}