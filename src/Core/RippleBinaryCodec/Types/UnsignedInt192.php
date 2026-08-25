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

/**
 * An unsigned 192 bit integer.
 *
 * Note that definitions.json calls the 24 byte type Hash192, which is handled
 * by the Hash192 class; this one is kept for callers that ask for UInt192.
 */
class UnsignedInt192 extends UnsignedInt
{
    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): UnsignedInt192
    {
        $bytes = $parser->readUIntN(24);
        return new UnsignedInt192($bytes);
    }

    public static function fromJson(string|int $serializedJson): SerializedType
    {
        if (is_int($serializedJson)) {
            $serializedJson = (string)$serializedJson;
        }

        return new UnsignedInt192(Buffer::from($serializedJson, 'hex'));
    }

    public function toBytes(): Buffer
    {
        $hexStr = $this->value->toBase(16);
        $uint192HexStr = str_pad($hexStr, 48, "0", STR_PAD_LEFT);

        return Buffer::from($uint192HexStr, 'hex');
    }

    public function valueOf(): int|string
    {
        return $this->value->toBase(10);
    }
}
