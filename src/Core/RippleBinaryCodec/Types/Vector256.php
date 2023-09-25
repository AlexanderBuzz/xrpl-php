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
use phpDocumentor\Reflection\DocBlock\StandardTagFactory;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BytesList;

class Vector256 extends SerializedType
{
    protected static int $width = 32;

    public function __construct(?Buffer $bytes = null)
    {
        if (is_null($bytes)) {
            $bytes = Buffer::alloc(static::$width); // 20 Zeros = XRP
        }

        parent::__construct($bytes);
    }

    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType
    {
        $bytesList = new BytesList();
        $numBytes = (!is_null($lengthHint)) ? $lengthHint : $parser->getSize();
        $numHashes = $numBytes / static::$width;

        for ($i = 0; $i < $numHashes; $i++) {
            $hash256 = new Hash256();
            $hash256->fromParser($parser)->toBytesSink($bytesList);
        }

        return new Vector256($bytesList->toBytes());
    }

    public static function fromJson(string $serializedJson): SerializedType
    {
        $bytesList = new BytesList();

        foreach (json_decode($serializedJson) as $hash) {
            $hash256 = new Hash256();
            $hash256->fromHex($hash)->toBytesSink($bytesList);
        }

        return new Vector256($bytesList->toBytes());
    }

    public function toJson(): array|string|int
    {
        if ($this->bytes->getLength() % 32 !== 0) {
            throw new \Exception('Invalid bytes for Vector256');
        }

        $result = [];

        for ($i = 0; $i < $this->bytes->getLength(); $i += 32) {
            $result[] = strtoupper($this->bytes->slice($i, $i + 32)->toString());
        }

        return json_encode($result);
    }
}