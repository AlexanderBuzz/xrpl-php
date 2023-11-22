<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XRPL_PHP\Core;

use ArrayAccess;
use Brick\Math\BigInteger;
use Exception;
use SplFixedArray;

/**
 *  Re-implements the functionality of node.js Buffer (https://nodejs.org/api/buffer.html) for
 *  serialization and deserialization
 */
class Buffer implements ArrayAccess
{
    const DEFAULT_FILL = 0x00;

    private SplFixedArray $bytesArray;

    public function __construct()
    {
        $this->bytesArray = SplFixedArray::fromArray([]);
    }

    /**
     * Creates a new buffer of given size
     *
     * @param int $size
     * @return Buffer
     * @throws Exception
     */
    public static function alloc(int $size = 0): Buffer
    {
        $tempArray = array_fill(0, $size, self::DEFAULT_FILL);

        return self::from($tempArray);
    }

    /**
     * Creates a new Buffer from different sources
     *
     * @param mixed $source
     * @param string|null $encoding
     * @return Buffer
     * @throws Exception
     */
    public static function from(mixed $source, ?string $encoding = 'hex'): Buffer
    {
        $buffer = new Buffer();

        //Duplicate buffer
        if (gettype($source) === 'object' && get_class($source) === Buffer::class) {
            Buffer::from($buffer->toArray());
        }

        //Buffer from byte array [12, 108, 0, 230]
        if (gettype($source) === 'array') {
            $bytesArray = SplFixedArray::fromArray($source);
            $buffer->setBytesArray($bytesArray);
            return $buffer;
        }

        //Buffer from empty string ''
        if (gettype($source) === 'string' && strlen($source) === 0) {
            return $buffer;
        }

        //Buffer from hex string 'ff03a5ed'
        if (gettype($source) === 'string' && $encoding === 'hex') {
            if(strlen($source)%2) {
                $source = '0' . $source;
            }
            $tempArray = array_map('hexdec', str_split($source, 2));
            $bytesArray = SplFixedArray::fromArray($tempArray);
            $buffer->setBytesArray($bytesArray);
            return $buffer;
        }

        //buffer from string 'hello world'
        if (gettype($source) === 'string' && $encoding == 'utf-8') {
            $tempArray = array_values(unpack('C*', $source));
            $bytesArray = SplFixedArray::fromArray($tempArray);
            $buffer->setBytesArray($bytesArray);
            return $buffer;
        }

        //buffer from Bricks/BigInteger
        if (get_class($source) === BigInteger::class) {
            return Buffer::from($source->toBase(16));
        }

        throw new Exception('Buffer not does not support source type');
    }

    /**
     * Creates a single buffer from an array of Buffers by concatenating them
     *
     * @param array $bufferList
     * @param int|null $totalLength
     * @return Buffer
     * @throws Exception
     */
    public static function concat(array $bufferList, ?int $totalLength = null): Buffer
    {
        if (empty($bufferList) || $totalLength === 0) {
            return self::from([]);
        }

        $tempArray = [];

        foreach ($bufferList as $buffer) {
            if (gettype($buffer) === 'object' && get_class($buffer) === Buffer::class) {
                $buffer = $buffer->toArray();
            }
            $tempArray = array_merge($tempArray, $buffer);
        }

        if (is_int($totalLength) && count($tempArray) > $totalLength) {
            $tempArray = array_slice($tempArray, 0, $totalLength);
        }

        return self::from($tempArray);
    }

    /**
     * Creates a random bytes filled Buffer of given size
     *
     * @param int $size
     * @return Buffer
     * @throws Exception
     */
    public static function random(int $size): Buffer
    {
        if ($size < 1) {
            return Buffer::alloc();
        }

        $hexBytes = bin2hex(random_bytes($size));
        return Buffer::from($hexBytes);
    }

    /**
     * Duplicates the Buffer
     *
     * @return Buffer
     * @throws Exception
     */
    public function clone(): Buffer
    {
        return Buffer::from($this->toArray());
    }

    /**
     * Returns Buffer size (number of bytes)
     *
     * @return int
     */
    public function getLength(): int
    {
        return $this->bytesArray->getSize();
    }

    /**
     * Concatenates given Buffer content
     *
     * @param Buffer $appendix
     * @return void
     */
    public function appendBuffer(Buffer $appendix): void
    {
        $this->bytesArray = SplFixedArray::fromArray(array_merge($this->toArray(), $appendix->toArray()));
    }

    /**
     * Concatenates a given hex string as bytes
     *
     * @param string $hexBytes
     * @return void
     */
    public function appendHex(string $hexBytes): void
    {
        $appendix = array_map('hexdec', str_split($hexBytes, 2));
        $this->bytesArray = SplFixedArray::fromArray(array_merge($this->toArray(), $appendix));
    }

    /**
     * Prepends the contents of a given Buffer
     *
     * @param Buffer $prefix
     * @return void
     */
    public function prependBuffer(Buffer $prefix): void
    {
        $this->bytesArray = SplFixedArray::fromArray(array_merge($prefix->toArray(), $this->toArray()));
    }

    /**
     * Prepends a given hex string as bytes
     *
     * @param string $hexBytes
     * @return void
     */
    public function prependHex(string $hexBytes): void
    {
        $prefix = array_map('hexdec', str_split($hexBytes, 2));
        $this->bytesArray = SplFixedArray::fromArray(array_merge($prefix, $this->toArray()));
    }

    /**
     * Overwrites the Buffer with a given sequence starting at given index. May increase the length of the Buffer.
     *
     * @param int $startIdx
     * @param array $bytes
     * @return void
     */
    public function set(int $startIdx = 0, array $bytes = []): void
    {
        $tempArray = $this->bytesArray->toArray();

        foreach ($bytes as $key => $byte) {
            $tempArray[$startIdx + (int) $key] = $byte;
        }

        $this->bytesArray = SplFixedArray::fromArray($tempArray);
    }

    /**
     * Performs slicing of the Buffer, returns the subset of bytes for given range
     *
     * @param int $start
     * @param int|null $end
     * @return Buffer
     * @throws Exception
     */
    public function subArray(int $start, ?int $end = null): Buffer
    {
        if ($end) {
            $length = $end - $start;
            $tempArray = array_slice($this->bytesArray->toArray(), $start, $length);
        } else {
            //account for differences in array slicing behavior in PHP and JavaScript
            $tempArray = array_slice($this->bytesArray->toArray(), $start);
        }

        return self::from($tempArray);
    }

    /**
     * Synonym for subArray function
     *
     * @param int $start
     * @param int|null $end
     * @return Buffer
     * @throws Exception
     */
    public function slice(int $start, ?int $end = null): Buffer
    {
        return $this->subArray($start, $end);
    }

    /**
     * Returns buffer content formatted as hex string
     *
     * @return string
     */
    public function toString(): string
    {
        $str = "";
        foreach ($this->bytesArray as $byte) {
            $singleByteHexStr = str_pad(dechex($byte), 2, "0", STR_PAD_LEFT);
            $str .= $singleByteHexStr;
        }

        return strtoupper($str);
    }

    /**
     * Returns Buffer content as array of bytes
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->bytesArray->toArray();
    }

    /**
     * Transforms Buffer content to Integer, assumed byte content can be interpreted as UInt
     *
     * @return int
     */
    public function toInt():int
    {
        return hexdec($this->toString());
    }

    /**
     * Returns numerical value of byte content, e.g. "ff,ff" -> "65535"
     *
     * @return string
     */
    public function toDecimalString(): string
    {
        return (string) hexdec($this->toString());
    }

    public function toUtf8(): string
    {
        $str = "";
        foreach ($this->bytesArray as $byte) {
            $str .= chr($byte);
        }

        return $str;
    }

    /**
     * Outputs byte content similar to console.log(Buffer) in node.js
     *
     * @return string
     */
    public function debug(): string
    {
        $str = "Buffer <";
        foreach ($this->bytesArray as $index => $byte) {
            $singleByteHexStr = str_pad(dechex($byte), 2, "0", STR_PAD_LEFT);
            $str .= $singleByteHexStr;
        }
        $str .= ">";

        return $str;
    }

    /**
     * Return byte content as fixed length array
     *
     * @return SplFixedArray
     */
    protected function getBytesArray(): SplFixedArray
    {
        return $this->bytesArray;
    }

    /**
     * Replace byte content
     *
     * @param SplFixedArray $bytesArray
     */
    protected function setBytesArray(SplFixedArray $bytesArray): void
    {
        $this->bytesArray = $bytesArray;
    }

    /**
     * Whether an offset exists
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->bytesArray[$offset]);
    }

    /**
     * Offset to retrieve
     *
     * @param mixed $offset
     *
     * @return int
     *
     * @throws Exception
     */
    public function offsetGet(mixed $offset): int //TODO: If this is to replace node buffer, type may be mixed
    {
        if (!isset($this->bytesArray[$offset])) {
            throw new Exception('Requested Buffer element out of bounds');
        }
        return $this->bytesArray[$offset];
    }

    /**
     * Offset to set
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->bytesArray[] = $value;
        } else {
            $this->bytesArray[$offset] = $value;
        }
    }

    /**
     * Offset to unset
     *
     * @param $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->bytesArray[$offset]);
    }
}