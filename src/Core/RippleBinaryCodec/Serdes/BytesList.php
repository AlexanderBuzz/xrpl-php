<?php

namespace XRPL_PHP\Core\RippleBinaryCodec\Serdes;

use ArrayAccess;
use XRPL_PHP\Core\Buffer;

/**
 * https://github.com/XRPLF/xrpl.js/blob/main/packages/ripple-binary-codec/src/serdes/binary-serializer.ts
 *
 * Bytes list is a collection of buffer objects
 */
class BytesList
{
    private array $bufferArray;

    public function __construct()
    {
        $this->bufferArray = [];
    }

    public function getLength(): int
    {
        return count($this->bufferArray);
    }

    public function grab(int $index): Buffer
    {
        return $this->bufferArray[$index]; //TODO: This is optimistic :)
    }

    public function deepGrab(int $bufferIndex, $byteIndex): int
    {
        return $this->bufferArray[$bufferIndex][$byteIndex];  //TODO: This is very optimistic :)
    }

    public function prepend(Buffer $prefix): BytesList
    {
        $this->bufferArray = array_merge([$prefix], $this->bufferArray);

        return $this;
    }

    public function push(Buffer $bytesArg): BytesList
    {
        $this->bufferArray[] = $bytesArg;

        return $this;
    }

    public function replace(int $index, Buffer $newElement)
    {
        $this->bufferArray[$index] =$newElement;
    }

    public function toBytesSink(BytesList $list): Buffer
    {
        $list->toBytesSink($list); //TODO: Is this necessary?
    }

    public function toBytes(): Buffer
    {
        $tempArray = [];

        foreach ($this->bufferArray as $buffer) {
            $tempArray = array_merge($tempArray, $buffer->toArray());
        }

        return Buffer::from($tempArray);
    }


}