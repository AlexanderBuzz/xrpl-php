<?php

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use phpDocumentor\Reflection\Types\String_;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;
use function MongoDB\BSON\fromJSON;

define('MAX_DROPS', BigDecimal::of("1e17"));
define('MIN_XRP', BigDecimal::of("1e-6"));

class Amount extends SerializedType
{
    public const DEFAULT_AMOUNT_HEX = "4000000000000000";

    public const ZERO_CURRENCY_AMOUNT_HEX = "8000000000000000";

    public const NATIVE_AMOUNT_BYTE_LENGTH = 8;

    public const CURRENCY_AMOUNT_BYTE_LENGTH = 48;

    public const MAX_IOU_PRECISION = 16;

    public const MIN_IOU_EXPONENT = -96;

    public const MAX_IOU_EXPONENT = 80;

    public function __construct(?Buffer $bytes = null)
    {
        if (!$bytes) {
            $bytes = Buffer::from(self::DEFAULT_AMOUNT_HEX, 'hex');
        }

        parent::__construct($bytes);
    }

    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType
    {
        $isXRP = $parser->peek() & 0x80;
        $numBytes = $isXRP ? 48 : 8; //NATIVE_AMOUNT_BYTE_LENGTH : CURRENCY_AMOUNT_BYTE_LENGTH;

        return new Amount($parser->read($numBytes));
    }

    public static function fromJson(string $serializedJson): SerializedType
    {
        $isScalar = preg_match('/^\d+$/', $serializedJson);
        if ($isScalar) {
            self::assertXrpIsValid($serializedJson);
            $padded = str_pad(dechex($serializedJson), 16, 0, STR_PAD_LEFT);
            $bytes = Buffer::from($padded, 'hex'); //padding!
            $rawBytes = $bytes->toArray();
            $rawBytes[0] |= 0x40;

            return new Amount(Buffer::from($rawBytes));
        }

        $isAmountObject = false;
        if ($isAmountObject) {
            //TODO: implement non-XRP amount Object
        }

        throw new \Exception('Invalid type to construct an Amount');
    }

    public function toJson(): string|array
    {
        $rawBytes = $this->bytes->toArray();
        if ($this->isNative($rawBytes)) {
            $rawBytes[0] &= 0x3f;

            $value = BigInteger::of(Buffer::from($rawBytes)->toDecimalString()); //TODO -> correct Input!
            if (!$this->isPositive($this->bytes->toArray())) {
                $value = $value->negated();
            }

            return (string)$value;
        }
    }

    private static function assertXrpIsValid(string $amount): void
    {
        if (str_contains($amount, ".")) {
            throw new \Exeption($amount . ' is an illegal amount');
        }

        $value = BigDecimal::of($amount);
        if (!$value->isZero()) {
            if ($value->compareTo(MIN_XRP) < 0 || $value->compareTo(MAX_DROPS) > 0) {
                throw new \Exeption($amount . ' is an illegal amount');
            }
        }
    }


    /**
     * @return bool
     */
    private function isNative(array $bytes): bool
    {
        // 1st bit in 1st byte is set to 0 for native XRP
        return ($bytes[0] & 0x80) == 0;
    }

    /**
     * @return bool
     */
    private function isPositive(array $bytes): bool
    {
        // 2nd bit in 1st byte is set to 1 for positive amounts
        return ($bytes[0] & 0x40) > 0;
    }

    /*
    private static function assertIouIsValid(BigDecimal $decimal): void
    {
        if (!$decimal->isZero()) {
            $precision = $decimal->
            $exponent = $decimal->

        }
    }
    */

    /*
    private static function verifyNonDecimal(BigDecimal $decimal): void
    {
       $exponent = new BigDecimal("1e" . -(MathUtilities.getExponent(decimal) - 15));
    }
    */
}