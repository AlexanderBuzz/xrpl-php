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

use Hardcastle\Buffer\Buffer;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

class UnsignedInt512 extends UnsignedInt
{
    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): UnsignedInt512
    {
        $bytes = $parser->readUIntN(64);
        return new UnsignedInt512($bytes);
    }

    public static function fromJson(string|int $serializedJson): SerializedType
    {
        if (is_int($serializedJson)) {
             $serializedJson = (string)$serializedJson;
        }

        return new UnsignedInt512(Buffer::from($serializedJson));
    }

    public function toBytes(): Buffer
    {
        $hexStr = $this->value->toBase(16);
        $uint512HexStr = str_pad($hexStr, 128, "0", STR_PAD_LEFT);

        return Buffer::from($uint512HexStr, 'hex');
    }

    public function valueOf(): int|string
    {
        return $this->value->toBase(10);
    }
}
