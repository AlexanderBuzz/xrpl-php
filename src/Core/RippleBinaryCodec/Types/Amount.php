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

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use Exception;
use Hardcastle\Buffer\Buffer;
use Hardcastle\XRPL_PHP\Core\MathUtilities;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;
define('MAX_DROPS', BigDecimal::of("1e17"));
define('MIN_XRP', BigDecimal::of("1e-6"));

class Amount extends SerializedType
{
    public const DEFAULT_AMOUNT_HEX = "4000000000000000";

    public const ZERO_CURRENCY_AMOUNT_HEX = "8000000000000000";

    public const NATIVE_AMOUNT_BYTE_LENGTH = 8;

    public const CURRENCY_AMOUNT_BYTE_LENGTH = 48;

    public const MPT_AMOUNT_BYTE_LENGTH = 33;

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
        $isIou = $parser->peek() & 0x80;
        if ($isIou) {
            return new Amount($parser->read(self::CURRENCY_AMOUNT_BYTE_LENGTH));
        }

        // At this point the amount is either XRP or MPT
        $isMpt = $parser->peek() & 0x20;
        $numBytes = $isMpt ? self::MPT_AMOUNT_BYTE_LENGTH : self::NATIVE_AMOUNT_BYTE_LENGTH;

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

        $json = json_decode($serializedJson, true);

        if (!is_array($json)) {
            throw new Exception('Invalid type to construct an Amount');
        }

        if (self::isMptAmountObject($json)) {
            return self::mptFromJson($json);
        }

        if (!self::isIouAmountObject($json)) {
            throw new Exception('Invalid type to construct an Amount');
        }

        [
            'value' => $rawValue,
            'currency' => $rawCurrency,
            'issuer' => $rawIssuer
        ] = $json;

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
            $amount = Buffer::from($hex1 . $hex2, 'hex');

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
        } else if ($this->isMpt($rawBytes)) {
            $parser = new BinaryParser($this->toHex());
            $leadingByte = $parser->read(1);
            $value = BigInteger::fromBase($parser->read(8)->toString(), 16);
            $mptIssuanceId = Hash192::fromParser($parser);

            if (!($leadingByte[0] & 0x40)) {
                $value = $value->negated();
            }

            return [
                'value' => (string)$value,
                'mpt_issuance_id' => $mptIssuanceId->toJson()
            ];
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
                'currency' => $currency->toJson(),
                'issuer' => $issuer->toJson(),
                'value' => MathUtilities::trimAmountZeros($value)
            ];
        }
    }

    /**
     * Check if the given amount is a valid XRP or IOU/token amount.
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
        } catch (Exception) {
            // Do nothing
        }

        return false;
    }

    /**
     * Type guard for an IOU amount object
     *
     * @param array $json
     * @return bool
     */
    private static function isIouAmountObject(array $json): bool
    {
        $keys = array_keys($json);
        sort($keys);

        return count($keys) === 3
            && $keys[0] === 'currency'
            && $keys[1] === 'issuer'
            && $keys[2] === 'value';
    }

    /**
     * Type guard for an MPT amount object
     *
     * @param array $json
     * @return bool
     */
    private static function isMptAmountObject(array $json): bool
    {
        $keys = array_keys($json);
        sort($keys);

        return count($keys) === 2
            && $keys[0] === 'mpt_issuance_id'
            && $keys[1] === 'value';
    }

    /**
     * Build an MPT amount from its JSON representation
     *
     * @param array $json
     * @return Amount
     * @throws Exception
     */
    private static function mptFromJson(array $json): Amount
    {
        self::assertMptIsValid($json['value']);

        $value = BigInteger::of($json['value']);

        $leadingByte = 0x60;
        if ($value->isNegative()) {
            $leadingByte = 0x20;
            $value = $value->abs();
        }

        $amount = Buffer::from(
            str_pad($value->toBase(16), 16, '0', STR_PAD_LEFT),
            'hex'
        );
        $mptIssuanceId = Hash192::fromJson($json['mpt_issuance_id'])->toBytes();

        return new Amount(Buffer::concat([
            Buffer::from([$leadingByte]),
            $amount,
            $mptIssuanceId
        ]));
    }

    /**
     * Validate an MPT amount value
     *
     * @param string $amount
     * @return void
     * @throws Exception
     */
    private static function assertMptIsValid(string $amount): void
    {
        if (str_contains($amount, ".")) {
            throw new Exception($amount . ' is an illegal amount');
        }

        $value = BigInteger::of($amount);
        if (!$value->isZero()) {
            if ($value->isNegative()) {
                throw new Exception($amount . ' is an illegal amount');
            }

            // The most significant bit of the 64 bit value is reserved
            if ($value->isGreaterThan(BigInteger::of(2)->power(63)->minus(1))) {
                throw new Exception($amount . ' is an illegal amount');
            }
        }
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
            $exponent = MathUtilities::getBigDecimalExponent($number) - 15;

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
        // 1st bit in 1st byte is set to 0 for native XRP, the 3rd bit
        // distinguishes XRP (0) from MPT (1)
        return ($bytes[0] & 0x80) == 0 && ($bytes[0] & 0x20) == 0;
    }

    /**
     * Test if this amount is in units of an MPT (Multi-Purpose Token)
     *
     * @param array $bytes
     * @return bool
     */
    private function isMpt(array $bytes): bool
    {
        return ($bytes[0] & 0x80) == 0 && ($bytes[0] & 0x20) != 0;
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