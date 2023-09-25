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

class UnsignedInt64 extends UnsignedInt
{
    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): UnsignedInt64
    {
        $hexValue = $parser->readUInt64()->toString();
        return new UnsignedInt64(Buffer::from($hexValue, 'hex'));
    }

    public static function fromJson(string $serializedJson): UnsignedInt64
    {
        $bigInteger = BigInteger::fromBase($serializedJson, 10);
        return new UnsignedInt64(Buffer::from($bigInteger->toBase(16)));
    }

    public function toBytes(): Buffer
    {
        $hexStr = $this->value->toBase(16);
        $uint64HexStr = str_pad($hexStr, 16, "0", STR_PAD_LEFT);

        return Buffer::from($uint64HexStr, 'hex');
    }

    public function valueOf(): int|string
    {
        return $this->value->toBase(10);
    }
}