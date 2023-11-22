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

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use Exception;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\MathUtilities;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;
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

    /**
     * Class for serializing/Deserializing Amounts
     *
     * @param Buffer|null $bytes
     * @throws Exception
     */
    public function __construct(?Buffer $bytes = null)
    {
        if (!$bytes) {
            $bytes = Buffer::from(self::DEFAULT_AMOUNT_HEX, 'hex');
        }

        parent::__construct($bytes);
    }

    /**
     * Read an amount from a BinaryParser
     *
     * @param BinaryParser $parser
     * @param int|null $lengthHint
     * @return SerializedType
     * @throws Exception
     */
    public static function fromParser(BinaryParser $parser, ?int $lengthHint = null): SerializedType
    {
        $isXRP = $parser->peek() & 0x80;
        $numBytes = $isXRP ? self::CURRENCY_AMOUNT_BYTE_LENGTH : self::NATIVE_AMOUNT_BYTE_LENGTH;

        return new Amount($parser->read($numBytes));
    }

    /**
     *  Creates an Amount object from a JSON string
     *
     * @param string $serializedJson
     * @return SerializedType
     * @throws MathException
     */
    public static function fromJson(string $serializedJson): SerializedType
    {
        $isScalar = preg_match('/^\d+$/', $serializedJson);
        if ($isScalar) {
            self::assertXrpIsValid($serializedJson);
            $padded = str_pad(dechex((int)$serializedJson), 16, '0', STR_PAD_LEFT);
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
        self::assertIouIsValid($number);

        if($number->isZero()) {
            $amount[0] |= 0x80;
        } else {
            $intString = str_pad($number->getUnscaledValue()->abs()->jsonSerialize(), 16, '0');
            $bigInteger = BigInteger::of($intString);

            $hex1 = str_pad($bigInteger->shiftedRight(32)->toBase(16), 8, '0', STR_PAD_LEFT);
            $hex2 = str_pad($bigInteger->and(0x00000000ffffffff)->toBase(16), 8, '0', STR_PAD_LEFT);
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

    /**
     * Returns the JSON representation of an Amount object as a string or array
     *
     * @return string|array
     * @throws MathException
     */
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
        } else {
            $binaryParser = new BinaryParser($this->toHex());
            $mantissa = $binaryParser->read(8);
            $currency = Currency::fromParser($binaryParser);
            $issuer = AccountId::fromParser($binaryParser);

            $b1 = $mantissa[0];
            $b2 = $mantissa[1];

            $isPositive = $b1 & 0x40;
            $sign = $isPositive ? '' : '-';
            $exponent = (($b1 & 0x3f) << 2) + (($b2 & 0xff) >> 6) - 97;

            $mantissa[0] = 0;
            $mantissa[1] &= 0x3f;
            $decimal = $sign . hexdec($mantissa->toString());
            $value = BigDecimal::ofUnscaledValue($decimal)->multipliedBy('1e' . $exponent);

            self::assertIouIsValid($value);

            return [
                'value' => MathUtilities::trimAmountZeros($value),
                'currency' => $currency->toJson(),
                'issuer' => $issuer->toJson()
            ];
        }
    }

    /**
     *
     *
     * @param mixed $amount
     * @return bool
     */
    public static function isAmountValid(mixed $amount): bool
    {
        try {
            self::assertXrpIsValid($amount);
            // If no exception is thrown, it's a valid XRP amount
            return true;
        } catch (Exception $exception) {
            // Do nothing
        }

        try {
            self::assertIouIsValid($amount);
            // If no exception is thrown, it's a valid IOU/token amount
            return true;
        } catch (Exception $exception) {
            // Do nothing
        }

        return false;
    }

    /**
     *  Validate XRP amount
     *
     * @param string $amount
     * @return void
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
     * Validate IOU.value amount
     *
     * @param BigDecimal $number
     * @return void
     * @throws Exception
     */
    private static function assertIouIsValid(BigDecimal $number): void
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

            self::verifyNoDecimal($number);
        }
    }

    private static function verifyNoDecimal(BigDecimal $decimal): void
    {
        $intString = str_pad($decimal->getUnscaledValue()->abs()->jsonSerialize(), 16, '0');

        if (str_contains($intString, '.')) {
            throw new Exception("Decimal place found in integerNumberString");
        }
    }


    /**
     * Test if this amount is in units of Native Currency(XRP)
     *
     * @param array $bytes
     * @return bool
     */
    private function isNative(array $bytes): bool
    {
        // 1st bit in 1st byte is set to 0 for native XRP
        return ($bytes[0] & 0x80) == 0;
    }

    /**
     * Test if bytes represent a positive amount
     *
     * @param array $bytes
     * @return bool
     */
    private function isPositive(array $bytes): bool
    {
        // 2nd bit in 1st byte is set to 1 for positive amounts
        return ($bytes[0] & 0x40) > 0;
    }
}