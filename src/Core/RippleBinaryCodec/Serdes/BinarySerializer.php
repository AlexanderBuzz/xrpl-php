<?php

namespace XRPL_PHP\Core\RippleBinaryCodec\Serdes;

use XRPL_PHP\Core\Buffer;

class BinarySerializer
{
    private Buffer $bytes;

    public function __construct(Buffer $bytes)
    {
        $this->bytes = $bytes;
    }

    public function put(string $hexBytes)
    {
        $this->bytes->append($hexBytes);
    }

    public function writeFieldAndValue()
    {

    }
}