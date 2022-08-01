<?php

namespace XRPL_PHP\Core\RippleBinaryCodec\Serdes;

use XRPL_PHP\Core\Buffer;

/**
 * https://github.com/XRPLF/xrpl.js/blob/main/packages/ripple-binary-codec/src/serdes/binary-serializer.ts
 *
 * Bytes list is a collection of buffer objects
 */
class BytesList //TODO: let's see if we can get rid of this...
{
    private array $bufferList;

    public function __construct()
    {
        $this->bufferList = [];
    }

    public function getLength(): int
    {
        //Is this the lengt of the array or of all bytes in the Array?
        return count($this->bufferList);
    }

    public function push(Buffer $bytesArg): BytesList
    {
        $this->bufferList[] = $bytesArg;

        return $this;
    }

    public function toBytesSink(BytesList $list): Buffer
    {
        $list->toBytesSink($list); //TODO: retrun type?
    }

    public function toBytes(): Buffer
    {
        $tempArray = [];

        foreach ($this->bufferList as $buffer) {
            $tempArray = array_merge($tempArray, $buffer->toArray());
        }

        return Buffer::from($tempArray);
    }
}