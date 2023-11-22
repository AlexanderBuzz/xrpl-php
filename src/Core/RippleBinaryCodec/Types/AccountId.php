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

use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleAddressCodec\AddressCodec;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

class AccountId extends Hash160
{
    private const HEX_REGEX = "/^[A-F0-9]{40}$/";

    public function __construct(?Buffer $bytes = null)
    {
        if ($bytes === null) {
            $bytes = Buffer::alloc(static::$width);
        }

        parent::__construct($bytes);
    }

    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType
    {
        return new AccountId($parser->read(static::$width));
    }

    public static function fromJson(string $serializedJson): SerializedType
    {
        if ($serializedJson === '') {
            return new AccountId();
        }

        $isHex = (preg_match(self::HEX_REGEX, $serializedJson) === 1);

        if ($isHex) {
            return new AccountId(Buffer::from($serializedJson));
        }

        $addressCodec = new AddressCodec();

        return new AccountId($addressCodec->decodeAccountId($serializedJson));
    }

    public function toJson(): array|string|int
    {
        $addressCodec = new AddressCodec();

        return $addressCodec->encodeAccountId($this->bytes);
    }
}