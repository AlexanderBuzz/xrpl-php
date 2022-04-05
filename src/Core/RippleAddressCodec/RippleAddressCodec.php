<?php declare(strict_types = 1);

namespace XRPL_PHP\RippleAddressCodec;

use XRPL_PHP\RippleAddressCodec\CodecWithXrpAlphabet;
use XRPL_PHP\RippleAddressCodec\Utils;

class RippleAddressCodec extends CodecWithXrpAlphabet
{
    public function __contructor()
    {
        parent::__constructor(Utils::XRPL_ALPHABET);
    }

    public function classicAddressToXAddress()
    {

    }

    public function encodeXAddress()
    {

    }

    public function xAddressToClassicAddress()
    {

    }

    public function decodeXAddress()
    {

    }

    public function isValidXAddress()
    {

    }

    private function isBufferForTestAddress(): bool
    {

    }

    private function tagFromBuffer()
    {

    }
}