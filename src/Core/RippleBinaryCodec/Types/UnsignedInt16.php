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

class UnsignedInt16 extends UnsignedInt
{
    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): UnsignedInt16
    {
        $bytes  = $parser->readUInt16();
        return new UnsignedInt16($bytes);
    }

    public static function fromJson(string|int $serializedJson): SerializedType
    {
        if (is_string($serializedJson)) {
            $serializedJson = (int) json_decode($serializedJson);
        }

        return new UnsignedInt16(Buffer::from(dechex($serializedJson)));
    }

    public function toBytes(): Buffer
    {
        $hexStr = $this->value->toBase(16);
        $uint16HexStr = str_pad($hexStr, 4, "0", STR_PAD_LEFT);

        return Buffer::from($uint16HexStr, 'hex');
    }

    public function valueOf(): int|string
    {
        return $this->value->toInt();
    }
}