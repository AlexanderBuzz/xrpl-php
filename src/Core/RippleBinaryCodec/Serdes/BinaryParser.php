<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Serdes;

use Exception;
use Hardcastle\Buffer\Buffer;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions\Definitions;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions\FieldHeader;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions\FieldInstance;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\SerializedType;

/**
 * BinaryParser is used to compute fields and values from a HexString
 *
 * https://github.com/XRPLF/xrpl.js/blob/main/packages/ripple-binary-codec/src/serdes/binary-parser.ts
 */
class BinaryParser
{
    private Buffer $bytes;

    private ?Definitions $definitions;

    /**
     *
     *
     * @param string $hexBytes
     * @throws Exception
     */
    public function __construct(string $hexBytes, ?Definitions $definitions = null)
    {
        $this->bytes = Buffer::from($hexBytes, 'hex');
        $this->definitions = $definitions;
    }

    /**
     * The definitions this parser reads field headers against.
     *
     * Defaults to the bundled XRP Ledger definitions. Another network passes
     * its own set in, and it travels with the parser through the whole decode.
     *
     * @return Definitions
     * @throws \Exception
     */
    public function getDefinitions(): Definitions
    {
        return $this->definitions ??= Definitions::getInstance();
    }
    /**
     * The next byte without consuming it.
     *
     * @return int
     * @throws Exception
     */
    public function peek(): int
    {
        if ($this->bytes->getLength() > 0) {
            return $this->bytes[0];
        }

        throw new \Exception('Buffer is empty');
    }
    /**
     * Discard the next $n bytes.
     *
     * @param int $number
     * @return void
     * @throws Exception
     */
    public function skip(int $number): void
    {
        if ($this->bytes->getLength() >= $number) {
            $this->bytes = $this->bytes->slice($number);
        } else {
            throw new Exception('Trying to skip more elements than the buffer has');
        }
    }
    /**
     * Consume and return the next $n bytes.
     *
     * @param int $number
     * @return Buffer
     * @throws Exception
     */
    public function read(int $number): Buffer
    {
        if ($this->bytes->getLength() >= $number) {
            $slice = $this->bytes->slice(0, $number);
            $this->skip($number);

            return $slice;
        }

        throw new Exception('Trying to read more elements than the buffer has');
    }
    /**
     * Consume $n bytes and read them as a big endian unsigned integer.
     *
     * @param int $number
     * @return Buffer
     * @throws Exception
     */
    public function readUIntN(int $number): Buffer //BigInteger
    {
        if ($number > 0) {
            return $this->read($number);
        }

        throw new Exception('Invalid number');
    }

    /**
     * Consume one byte as an unsigned integer.
     */
    public function readUInt8(): Buffer
    {
        return $this->readUIntN(1);
    }
    /**
     * Consume two bytes as an unsigned integer.
     *
     * @return Buffer
     * @throws Exception
     */
    public function readUInt16(): Buffer
    {
        return $this->readUIntN(2);
    }
    /**
     * Consume four bytes as an unsigned integer.
     *
     * @return Buffer
     * @throws Exception
     */
    public function readUInt32(): Buffer
    {
        return $this->readUIntN(4);
    }
    /**
     * Consume eight bytes as an unsigned integer.
     *
     * @return Buffer
     * @throws Exception
     */
    public function readUInt64(): Buffer
    {
        return $this->readUIntN(8);
    }
    /**
     * Whether the stream is exhausted.
     *
     * @param int|null $customEnd
     * @return bool
     */
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
    /**
     * Read the type and field ordinal that introduce the next field.
     * Both are packed into one byte where they are small enough, and spill into
     * following bytes otherwise, which is why this is not a fixed width read.
     *
     * @return FieldHeader
     * @throws Exception
     */
    public function readFieldHeader(): FieldHeader
    {
        $typeCode = $this->readUInt8()->toInt();
        $nth = $typeCode & 15;
        $typeCode = $typeCode >> 4;

        if ($typeCode === 0) {
            $typeCode = $this->readUInt8()->toInt();
            if ($typeCode < 16) {
                throw new Exception("Cannot read FieldOrdinal, type_code out of range");
            }
        }

        if ($nth === 0) {
            $nth = $this->readUInt8()->toInt();
            if ($nth < 16) {
                throw new Exception("Cannot read FieldOrdinal, field_code out of range");
            }
        }

        return new FieldHeader($typeCode, $nth);
    }
    /**
     * Read the next field header and resolve it to a field of the definitions.
     *
     * @return FieldInstance
     * @throws Exception
     */
    public function readField(): FieldInstance
    {
        $fieldHeader = $this->readFieldHeader();
        $fieldName = $this->getDefinitions()->getFieldNameFromHeader($fieldHeader);

        return $this->getDefinitions()->getFieldInstance($fieldName);
    }
    /**
     * Read one value of the given type from the stream.
     *
     * @param SerializedType $type
     * @return SerializedType
     */
    public function readType(SerializedType $type): SerializedType
    {
        return $type->fromParser($this);
    }

    /**
     * The type class that handles a field's values.
     */
    public function typeForField(FieldInstance $field): SerializedType
    {
        return SerializedType::getTypeByName($field->getType());
    }

    /**
     * Read the value belonging to a field, taking its length from the stream first
     * where the field is variable length.
     */
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
    /**
     * Read the length prefix of a variable length field.
     * The prefix is one to three bytes; which it is follows from the first one.
     *
     * @return int
     * @throws Exception
     */
    public function readVariableLengthLength(): int
    {
        $firstByte = $this->readUInt8()->toInt(); //BigInt?

        if ($firstByte <= 192) {
            return $firstByte;
        } else if ($firstByte <= 240) {
            $secondByte = $this->readUInt8()->toInt();
            return 193 + ($firstByte - 193) * 256 + $secondByte;
        } else if ($firstByte <= 254) {
            $secondByte = $this->readUInt8()->toInt();
            $thirdByte = $this->readUInt8()->toInt();
            return 12481 + ($firstByte - 241) * 65536 + $secondByte * 256 + $thirdByte;
        }

        throw new Exception("Invalid variable length indicator");
    }

    /**
     * Returns internal Buffer length
     *
     * @return int
     */
    public function getSize(): int
    {
        return $this->bytes->getLength();
    }

}