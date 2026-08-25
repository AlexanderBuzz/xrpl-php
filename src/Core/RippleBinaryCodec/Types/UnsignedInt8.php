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

use Brick\Math\BigInteger;
use Hardcastle\Buffer\Buffer;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

/**
 * An unsigned 8 bit integer, such as TransactionResult or AssetScale.
 */
class UnsignedInt8 extends UnsignedInt
{
    public const WIDTH = 1;

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

        return new UnsignedInt8(Buffer::from(str_pad(dechex($serializedJson), 2, '0', STR_PAD_LEFT), 'hex'));
    }

    public function valueOf(): int|string
    {
        return $this->value->toInt();
    }
}