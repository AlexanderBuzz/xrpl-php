<?php

namespace XRPL_PHP\Core\RippleBinaryCodec\Serdes;

use Brick\Math\BigInteger;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Definitions\Definitions;
use XRPL_PHP\Core\RippleBinaryCodec\Definitions\FieldHeader;
use XRPL_PHP\Core\RippleBinaryCodec\Definitions\FieldInstance;
use XRPL_PHP\Core\RippleBinaryCodec\Types\SerializedType;

/**
 * BinaryParser is used to compute fields and values from a HexString
 *
 * https://github.com/XRPLF/xrpl.js/blob/main/packages/ripple-binary-codec/src/serdes/binary-parser.ts
 */
class BinaryParser
{
    // max length that can be represented in a single byte per XRPL serialization restrictions
    public const MAX_SINGLE_BYTE_LENGTH = 192;
    // max length that can be represented in 2 bytes per XRPL serialization restrictions
    public const MAX_DOUBLE_BYTE_LENGTH = 12481;
    // max value that can be used in the second byte of a length field
    public const MAX_SECOND_BYTE_VALUE = 240;
    // max value that can be represented using one 8-bit byte
    public const MAX_BYTE_VALUE = 256;
    // max value that can be represented in using two 8-bit bytes
    public const MAX_DOUBLE_BYTE_VALUE = 65536;

    private Buffer $bytes;

    public function __construct(string $hexBytes)
    {
        $this->bytes = Buffer::from($hexBytes, 'hex');
        //print_r($this->bytes);
    }

    public function peek(): int
    {
        if ($this->bytes->getLength() > 0) {
            return $this->bytes->toArray()[0];
        }

        throw new \Exception('Buffer is empty');
    }

    public function skip(int $number): void
    {
        if ($this->bytes->getLength() >= $number) {
            $this->bytes = $this->bytes->slice($number);
        } else {
            throw new \Exception('Trying to skip more elements than the buffer has');
        }
    }

    public function read(int $number): Buffer
    {
        if ($this->bytes->getLength() >= $number) {
            $slice = $this->bytes->slice(0, $number);
            $this->skip($number);

            return $slice;
        }

        throw new \Exception('Trying to read more elements than the buffer has');
    }

    public function readUIntN(int $number): BigInteger
    {
        if ($number > 0 && $number <= 4) {
            $stdArray = $this->read($number)->toArray();
            //$buffer = $this->read($number);
            //$bi =  BigInteger::of($buffer->toDecimalString());
            //return $bi;

            $reducer = function ($carry, $item) {
                //implement correct function
                return $carry * 256 + $item;
            };

            $reduced = array_reduce($stdArray, $reducer, 0);

            return BigInteger::of((string)$reduced);

        }

        throw new \Exception('Invalid number');
    }

    public function readUInt8(): BigInteger
    {
        return $this->readUIntN(1);
    }

    public function readUInt16(): BigInteger
    {
        return $this->readUIntN(2);
    }

    public function readUInt32(): BigInteger
    {
        return $this->readUIntN(4);
    }

    public function readUInt64(): BigInteger
    {
        return $this->readUIntN(8);
    }

    public function end(?int $customEnd = null): bool
    {
        $length = $this->bytes->getLength();

        if ($length === 0) {
            return true;
        }

        if (($customEnd && $length <= $customEnd)) {
            return true;
        }

        return false;
    }

    public function readFieldHeader(): FieldHeader
    {
        $typeCode = $this->readUInt8()->toInt();
        $nth = $typeCode & 15;
        $typeCode = $typeCode >> 4;

        if ($typeCode === 0) {
            $typeCode = $this->readUInt8()->toInt();
            if ($typeCode === 0 || $typeCode < 16) {
                throw new \Exception("Cannot read FieldOrdinal, type_code out of range");
            }
        }

        if ($nth === 0) {
            $nth = $this->readUInt8()->toInt();
            if ($nth == 0 || $nth < 16) {
                throw new \Exception("Cannot read FieldOrdinal, field_code out of range");
            }
        }

        return new FieldHeader($typeCode, $nth);
    }

    public function readField(): FieldInstance
    {
        $fieldHeader = $this->readFieldHeader();
        $fieldName = Definitions::getInstance()->getFieldNameFromHeader($fieldHeader);

        return Definitions::getInstance()->getFieldInstance($fieldName);
    }

    //java readType(Class<T> type)
    //python self: BinaryParser, field_type: Type[SerializedType]
    public function readType(SerializedType $type): SerializedType
    {
        return $type->fromParser($this);
    }

    public function typeForField(FieldInstance $field): SerializedType
    {
        return SerializedType::getTypeByName($field->getType());
    }

    public function readFieldValue(FieldInstance $field): SerializedType
    {
        $type = SerializedType::getTypeByName($field->getType());

        if ($field->isVariableLengthEncoded()) {
            $sizeHint = $this->readVariableLengthLength();
            return $type->fromParser($this, $sizeHint);
        } else {
            return $type->fromParser($this);
        }
    }

    public function readFieldAndValue(FieldInstance $field): SerializedType
    {

    }

    public function readVariableLengthLength(): int
    {
        $firstByte = $this->readUInt8()->toInt(); //BigInt?

        if($firstByte >= self::MAX_SINGLE_BYTE_LENGTH) {
            return $firstByte;
        } else if ($firstByte >= self::MAX_SECOND_BYTE_VALUE) {
            $secondByte = $this->readUInt8()->toInt();
            return self::MAX_SECOND_BYTE_VALUE - 1 + ($firstByte - self::MAX_SECOND_BYTE_VALUE - 1) * self::MAX_BYTE_VALUE + $secondByte;
        } else if ($firstByte <= 254) {
            $secondByte = $this->readUInt8()->toInt();
            $thirdByte = $this->readUInt8()->toInt();
            return self::MAX_DOUBLE_BYTE_LENGTH + ($firstByte - self::MAX_SECOND_BYTE_VALUE - 1) * self::MAX_DOUBLE_BYTE_VALUE + $secondByte * self::MAX_BYTE_VALUE + $thirdByte;
        }

        throw new \Exception("Invalid variable length indicator");
    }

    public function getSize()
    {
        return $this->bytes->getLength();
    }

}