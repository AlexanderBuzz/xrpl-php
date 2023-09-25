<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XRPL_PHP\Core\RippleBinaryCodec\Serdes;

use XRPL_PHP\Core\Buffer;

/**
 * This class is a list is a collection of buffer objects ("array of byte arrays"))
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

    /**
     * @psalm-param 0 $byteIndex
     */
    public function deepGrab(int $bufferIndex, int $byteIndex): int
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

    public function replace(int $index, Buffer $newElement): void
    {
        $this->bufferArray[$index] =$newElement;
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