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
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

class UnsignedInt8 extends UnsignedInt
{
    const WIDTH = 32 / 8; //4

    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): UnsignedInt8
    {
        $bytes = $parser->readUInt8();
        return new UnsignedInt8($bytes );
    }

    public static function fromJson(string|int $serializedJson): SerializedType
    {
        if (is_string($serializedJson)) {
            $serializedJson = (int) json_decode($serializedJson);
        }

        return new UnsignedInt8(Buffer::from(dechex($serializedJson)));
    }

    public function valueOf(): int|string
    {
        return $this->value->toInt();
    }
}