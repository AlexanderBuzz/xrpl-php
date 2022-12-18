<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use BI\BigInteger;
use phpDocumentor\Reflection\DocBlock\StandardTagFactory;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BytesList;
use function MongoDB\BSON\fromJSON;

class Blob extends SerializedType
{
    public function __construct(?Buffer $bytes = null)
    {
        if (is_null($bytes)) {
            $bytes = Buffer::alloc(0);
        }

        parent::__construct($bytes);
    }

    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType
    {
        if (is_null($lengthHint)) {
            $lengthHint = $parser->getSize();
        }
        return new Blob($parser->read($lengthHint));
    }

    public static function fromJson(string $serializedJson): SerializedType
    {
        return new Blob(Buffer::from($serializedJson));
    }
}