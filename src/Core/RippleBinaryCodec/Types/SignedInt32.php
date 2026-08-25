<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types;

use Brick\Math\BigInteger;
use Hardcastle\Buffer\Buffer;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

/**
 * A signed 32 bit integer.
 */
class SignedInt32 extends SignedInt
{
    public const WIDTH = 4;

    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): SignedInt32
    {
        return new SignedInt32($parser->readUInt32());
    }

    public static function fromJson(string|int $serializedJson): SerializedType
    {
        if (is_string($serializedJson)) {
            $serializedJson = (int) json_decode($serializedJson);
        }

        $instance = new SignedInt32();
        $instance->value = BigInteger::of($serializedJson);

        return $instance;
    }

    public function valueOf(): int|string
    {
        return $this->value->toInt();
    }
}
