<?php

namespace XRPL_PHP\Core;

use SplFixedArray;

class Buffer
{
    const DEFAULT_FILL = 0x00;

    private SplFixedArray $bytesArray;

    public static function alloc(int $size = 0): Buffer
    {
        $tempArray = array_fill(0, $size, self::DEFAULT_FILL);

        return self::from($tempArray);
    }

    public static function from(mixed $source, ?string $encoding = null): Buffer
    {
        $buffer = new Buffer();

        if (gettype($source) === 'array') {
            $bytesArray = SplFixedArray::fromArray($source);
            $buffer->setBytesArray($bytesArray);
        }

        if (gettype($source) === 'string' && $encoding == 'hex') {
            $tempArray = array_map('hexdec', str_split($source, 2));
            $bytesArray = SplFixedArray::fromArray($tempArray);
            $buffer->setBytesArray($bytesArray);
        }

        return $buffer;
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

    public function getLength(): int
    {
        return $this->bytesArray->getSize();
    }

    public function subArray(int $start, ?int $end): Buffer
    {
        if ($end) {
            $length = $end - $start;
            $tempArray = array_slice($this->bytesArray->toArray(), $start, $length);
        } else {
            $tempArray = array_slice($this->bytesArray->toArray(), 0);
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

    public function toArray(): array
    {
        return $this->bytesArray->toArray();
    }

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
}