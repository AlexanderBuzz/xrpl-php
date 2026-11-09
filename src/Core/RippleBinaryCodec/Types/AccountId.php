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

use Exception;
use Hardcastle\Buffer\Buffer;
use Hardcastle\XRPL_PHP\Core\RippleAddressCodec\AddressCodec;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

/**
 * An account, 20 bytes on the wire.
 *
 * The JSON form is the familiar base58 classic address; the bytes are the
 * RIPEMD160 of the SHA256 of the public key, which is what the address encodes.
 *
 * As a field an account can also be unset, which is not the same as the zero
 * account: on the wire an empty field instead of 20 zero bytes, in JSON an
 * empty string instead of rrrrrrrrrrrrrrrrrrrrrhoLvTp. Xahau's Entropy pseudo
 * transaction looks like that. This follows STAccount in rippled, which keeps
 * 20 zero bytes and a flag, so that a value in a fixed width position, such as
 * the issuer of an Amount, stays 20 bytes long.
 */
class AccountId extends Hash160
{
    private const HEX_REGEX = "/^[A-F0-9]{40}$/";

    private bool $isDefault = false;

    public function __construct(?Buffer $bytes = null)
    {
        if ($bytes === null) {
            $bytes = Buffer::alloc(static::$width);
        }

        parent::__construct($bytes);
    }

    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType
    {
        if ($lengthHint === 0) {
            return self::unset();
        }

        if ($lengthHint !== null && $lengthHint !== static::$width) {
            throw new Exception("Invalid AccountID length {$lengthHint}");
        }

        return new AccountId($parser->read(static::$width));
    }

    public static function fromJson(string $serializedJson): SerializedType
    {
        if ($serializedJson === '') {
            return self::unset();
        }

        $isHex = (preg_match(self::HEX_REGEX, $serializedJson) === 1);

        if ($isHex) {
            return new AccountId(Buffer::from($serializedJson, 'hex'));
        }

        $addressCodec = new AddressCodec();

        return new AccountId($addressCodec->decodeAccountId($serializedJson));
    }

    private static function unset(): AccountId
    {
        $account = new AccountId();
        $account->isDefault = true;

        return $account;
    }

    /**
     * Whether this is an unset field rather than an account, the zero account
     * included. The serializer writes such a field with length zero.
     */
    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function toJson(): array|string|int
    {
        if ($this->isDefault) {
            return '';
        }

        $addressCodec = new AddressCodec();

        return $addressCodec->encodeAccountId($this->bytes);
    }
}