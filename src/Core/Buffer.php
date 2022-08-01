<?php

namespace XRPL_PHP\Core;

use ArrayAccess;
use Brick\Math\BigInteger;
use SplFixedArray;
use TheSeer\Tokenizer\Exception;

class Buffer implements ArrayAccess
{
    const DEFAULT_FILL = 0x00;

    private SplFixedArray $bytesArray;

    public static function alloc(int $size = 0): Buffer
    {
        $tempArray = array_fill(0, $size, self::DEFAULT_FILL);

        return self::from($tempArray);
    }

    public static function from(mixed $source, ?string $encoding = 'hex'): Buffer
    {
        $buffer = new Buffer();

        if (gettype($source) === Buffer::class ) {
            Buffer::from($buffer->toArray()); //TODO: likely unnecessary, check node Buffer
        }

        if (gettype($source) === 'array') {
            $bytesArray = SplFixedArray::fromArray($source);
            $buffer->setBytesArray($bytesArray);
            return $buffer;
        }

        if (gettype($source) === 'string' && $encoding == 'hex') {
            $tempArray = array_map('hexdec', str_split($source, 2));
            $bytesArray = SplFixedArray::fromArray($tempArray);
            $buffer->setBytesArray($bytesArray);
            return $buffer;
        }

        if (get_class($source) === BigInteger::class) {
            return Buffer::from($source->toBase(16), 'hex');
        }

        throw new \Exception('Buffer not does not support source type');
    }

    public static function concat(array $bufferList, ?int $totalLength = null): Buffer
    {
        if (empty($bufferList) || $totalLength === 0) {
            return self::from([]);
        }

        $tempArray = [];

        foreach ($bufferList as $buffer) {
            $tempArray = array_merge($tempArray, $buffer);
        }

        if ($totalLength === null) {
            $totalLength = 0;
            for ($i = 0; $i < count($bufferList); ++$i) {
                $totalLength += count($bufferList[$i]);
            }
        }

        if ($totalLength && count($tempArray) > $totalLength) {
            $tempArray = array_slice($tempArray, 0, $totalLength);
        }

        return self::from($tempArray);
    }

    public function clone(): Buffer
    {
        return Buffer::from($this->toArray());
    }

    public function getLength(): int
    {
        return $this->bytesArray->getSize();
    }

    public function appendBuffer(Buffer $appendix)
    {
        return Buffer::concat([$this->bytesArray, $appendix->toArray()]);
    }

    public function appendHex(string $hexBytes)
    {
        $toAttach = array_map('hexdec', str_split($hexBytes, 2));
        $this->bytesArray = SplFixedArray::fromArray(array_merge($this->bytesArray->toArray(), $toAttach));
    }

    public function set(int $startIdx, array $bytes): void
    {
        //TODO: check out of bounds
        foreach ($bytes as $key => $byte) {
            $this->bytesArray[$startIdx + $key] = $byte;
        }
    }

    public function subArray(int $start, ?int $end): Buffer
    {
        if ($end) {
            $length = $end - $start;
            $tempArray = array_slice($this->bytesArray->toArray(), $start, $length);
        } else {
            //account for differences in array slicing behavior in PHP and JavaScrip
            $tempArray = array_slice($this->bytesArray->toArray(), $start);
        }

        return self::from($tempArray);
    }

    public function slice(int $start, ?int $end = null): Buffer
    {
        return $this->subArray($start, $end);
    }

    public function toString(): string
    {
        $str = "";
        foreach ($this->bytesArray as $byte) {
            $singleByteHexStr = str_pad(dechex($byte), 2, "0", STR_PAD_LEFT);
            $str .= $singleByteHexStr;
        }

        return $str;
    }

    public function toDecimalString(): string
    {
        return hexdec($this->toString());
    }

    public function toArray(): array
    {
        return $this->bytesArray->toArray();
    }

    /*
    public function writeUInt32BE(BigInteger $value, int $offset, bool $noAssert = false): int
    {
        $offset = MathUtilities::unsignedRightShift($offset);

        //if (!noAssert) checkInt(this, value, offset, 4, 0xffffffff, 0);
        $this->bytesArray[$offset] = ($value->shiftedRight(24));
        $this->bytesArray[$offset + 1] = ($value->shiftedRight(16));
        $this->bytesArray[$offset + 2] = ($value->shiftedRight(8));
        //$this->bytesArray[$offset + 3] = ($value & 0xff);
        $this->bytesArray[$offset + 3] = ($value->and(0xff));

        return $offset + 4;
    }
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
     * @return SplFixedArray
     */
    protected function getBytesArray(): SplFixedArray
    {
        return $this->bytesArray;
    }

    /**
     * @param SplFixedArray $bytesArray
     */
    protected function setBytesArray(SplFixedArray $bytesArray): void
    {
        $this->bytesArray = $bytesArray;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->bytesArray[$offset]);
    }

    public function offsetGet($offset): int //TODO: If this is to replace node buffer, type may be mixed
    {
        if (!isset($this->bytesArray[$offset])) {
            throw new \Exception('Requested Buffer element out of bounds');
        }
        return $this->bytesArray[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->bytesArray[] = $value;
        } else {
            $this->bytesArray[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->bytesArray[$offset]);
    }
}