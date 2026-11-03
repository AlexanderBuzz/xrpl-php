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
 * A signed 64 bit integer.
 */
class SignedInt64 extends SignedInt
{
    public const WIDTH = 8;

    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType
    {
        return new SignedInt64($parser->readUInt64());
    }

    public static function fromJson(string|int $serializedJson): SerializedType
    {
        $instance = new SignedInt64();
        $instance->value = BigInteger::of($serializedJson);

        return $instance;
    }

    public function valueOf(): int|string
    {
        return (string) $this->value;
    }
}
