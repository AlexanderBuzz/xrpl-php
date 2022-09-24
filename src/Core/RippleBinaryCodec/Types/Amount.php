<?php

namespace XRPL_PHP\Core\RippleBinaryCodec\Types;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Exception;
use phpDocumentor\Reflection\Types\String_;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\MathUtilities;
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

    /**
     * @throws Exception
     */
    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType
    {
        $isXRP = $parser->peek() & 0x80;
        $numBytes = $isXRP ? 48 : 8; //NATIVE_AMOUNT_BYTE_LENGTH : CURRENCY_AMOUNT_BYTE_LENGTH;

        return new Amount($parser->read($numBytes));
    }

    /**
     * @throws Exception
     */
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

        [
            'value' => $rawValue,
            'currency' => $rawCurrency,
            'issuer' => $rawIssuer
        ] = json_decode($serializedJson, true);

        $amount = Buffer::alloc(8);

        $number = BigDecimal::of($rawValue);
        //self::assertIouIsValid($number);

        if($number->isZero()) {
            $amount[0] |= 0x80;
        } else {
            $intString = str_pad($number->getUnscaledValue()->abs(), 16, 0);
            $bigInteger = BigInteger::of($intString);

            $hex1 = str_pad($bigInteger->shiftedRight(32)->toBase(16), 8, 0, STR_PAD_LEFT);
            $hex2 = str_pad($bigInteger->and(0x00000000ffffffff)->toBase(16), 8, 0, STR_PAD_LEFT);
            $amount = Buffer::from($hex1 . $hex2);

            $amount[0] |= 0x80;

            if ($number->compareTo(BigDecimal::zero()) > 0) {
                $amount[0] |= 0x40;
            }

            $exponent = MathUtilities::getBigDecimalExponent($number)  - 15;
            $exponentByte = 97 + $exponent;
            $amount[0] |= MathUtilities::unsignedRightShift($exponentByte, 2);
            $amount[1] |= ($exponentByte & 0x03) << 6;
        }

        $currency = Currency::fromJson($rawCurrency)->toBytes();
        $issuer = AccountId::fromJson($rawIssuer)->toBytes();

        return new Amount(Buffer::from(array_merge($amount->toArray(), $currency->toArray(), $issuer->toArray())));
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

    /**
     * @throws Exception
     */
    private static function assertXrpIsValid(string $amount): void
    {
        if (str_contains($amount, ".")) {
            throw new Exception($amount . ' is an illegal amount');
        }

        $value = BigDecimal::of($amount);
        if (!$value->isZero()) {
            if ($value->compareTo(MIN_XRP) < 0 || $value->compareTo(MAX_DROPS) > 0) {
                throw new Exception($amount . ' is an illegal amount');
            }
        }
    }

    /**
     * @param BigDecimal $number
     * @return void
     * @throws Exception
     */
    private function assertIouIsValid(BigDecimal $number): void
    {
        if(!$number->isZero()) {
            $precision = MathUtilities::getBigDecimalPrecision($number);
            $exponent = MathUtilities::getBigDecimalExponent($number);

            if ($precision > self::MAX_IOU_EXPONENT ||
                $exponent > self::MAX_IOU_EXPONENT ||
                $exponent < self::MIN_IOU_EXPONENT
            ) {
                throw new Exception("Decimal precision out of range");
            }

            $this->verifyNoDecimal($number);
        }
    }

    private function verifyNoDecimal(BigDecimal $decimal): void
    {
        $intString = str_pad($decimal->getUnscaledValue()->abs(), 16, 0);

        if (str_contains($intString, '.')) {
            throw new Exception("Decimal place found in integerNumberString");
        }
    }

    private function getAmountBytes(BigDecimal $number): Buffer
    {

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
}