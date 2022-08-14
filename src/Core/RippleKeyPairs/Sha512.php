<?php

namespace XRPL_PHP\Core\RippleKeyPairs;

use Brick\Math\BigInteger;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\MathUtilities;

class Sha512
{
    private Buffer $bytes;

    public function __construct()
    {
        $this->bytes = Buffer::from([]);
    }

    public function add(Buffer $bytes)
    {
        $this->bytes->appendBuffer($bytes);
    }

    public function addUint32(BigInteger $i)
    {
        $tempArray = [
            ($i->shiftedRight(24)->toInt()) & 0xff,
            ($i->shiftedRight(16)->toInt()) & 0xff,
            ($i->shiftedRight(8)->toInt()) & 0xff,
            $i->toInt() & 0xff,
        ];

        $this->bytes->appendBuffer(Buffer::from($tempArray));
    }

    public function getFirst256(): Buffer
    {
        return MathUtilities::sha512Half($this->bytes);
    }

    public function getFirst256BN(): BigInteger
    {
        $first256 = $this->getFirst256();
        return BigInteger::of($first256->toDecimalString());
    }
}