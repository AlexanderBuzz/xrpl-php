<?php declare(strict_types=1);

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

    public static function fromSerializedJson(string $serializedJson): AccountId
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