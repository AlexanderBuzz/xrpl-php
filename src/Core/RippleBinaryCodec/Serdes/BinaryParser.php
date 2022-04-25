<?php

namespace XRPL_PHP\Core\RippleBinaryCodec\Serdes;

use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleBinaryCodec\Definitions\Definitions;
use XRPL_PHP\Core\RippleBinaryCodec\Definitions\FieldHeader;
use XRPL_PHP\Core\RippleBinaryCodec\Definitions\FieldInstance;
use XRPL_PHP\Core\RippleBinaryCodec\Types\SerializedType;
use function PHPUnit\Framework\throwException;

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
        }

        throw new \Exception('Trying to skip more elements than the buffer has');
    }

    public function read(int $number): Buffer
    {
        if ($this->bytes->getLength() >= $number) {
            $slice = $this->bytes->slice(0, $number);
            echo "num: " .print_r($slice->debug(), true) ." " ;
            $this->skip($number);
            return $slice;
        }

        throw new \Exception('Trying to read more elements than the buffer has');
    }

    public function readUIntN(int $number): int
    {
        if ($number > 0 && $number <= 4) {
            $stdArray = $this->read($number)->toArray();
            $reducer = function ($carry, $item) {
                //implement correct function
            };
            return array_reduce($stdArray, $reducer);
        }

        throw new \Exception('Invalid number');
    }

    public function readUInt8(): int
    {
        return $this->readUIntN(1);
    }

    public function readUInt16(): int
    {
        return $this->readUIntN(2);
    }

    public function readUInt32(): int
    {
        return $this->readUIntN(4);
    }

    public function readUInt64(): int
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
        $typeCode = $this->readUInt8();
        $fieldCode = $typeCode & 15;

        if ($typeCode === 0) {
            $typeCode = $this->readUInt8();
            if ($typeCode == 0 || $typeCode < 16) {
                throw new \Exception("Cannot read FieldOrdinal, type_code out of range");
            }
        }

        if ($fieldCode === 0) {
            $fieldCode = $this->readUInt8();
            if ($fieldCode == 0 || $fieldCode < 16) {
                throw new \Exception("Cannot read FieldOrdinal, field_code out of range");
            }
        }

        return new FieldHeader($typeCode, $fieldCode);
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
        return $type::fromParser($this);
    }

    public function typeForField(FieldInstance $field): SerializedType
    {

    }

    public function readFieldValue(FieldInstance $field): SerializedType
    {

    }

    public function readFieldAndValue(FieldInstance $field): SerializedType
    {

    }

}