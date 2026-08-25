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

use BI\BigInteger;
use phpDocumentor\Reflection\DocBlock\StandardTagFactory;
use Hardcastle\Buffer\Buffer;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Serdes\BytesList;
use function MongoDB\BSON\fromJSON;

/**
 * Arbitrary bytes of variable length, such as a Memo, a URI or a signature.
 *
 * The length is not part of the value; it is written by the field header.
 */
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
        return new Blob(Buffer::from($serializedJson, 'hex'));
    }
}