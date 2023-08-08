<?php

namespace XRPL_PHP\Test\Utils;

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Utils\Utilities;

class UtilitiesTest extends TestCase
{
    /*
    public function testIsHex(): void
    {

    }

    public function testIsoToHex(): void
    {

    }
    */

    public function testConvertStringToHex(): void
    {
        $str = 'example.com';
        $res = '6578616D706C652E636F6D';

        $this->assertEquals($res, Utilities::convertStringToHex($str));

        $str = 'ipfs://bafybeigdyrzt5sfp7udm7hu76uh7y26nf4dfuylqabf3oclgtqy55fbzdi';
        $res = '697066733A2F2F62616679626569676479727A74357366703775646D37687537367568377932366E6634646675796C71616266336F636C67747179353566627A6469';

        $this->assertEquals($res, Utilities::convertStringToHex($str));
    }

    public function testConvertHexToString(): void
    {
        $hex = '6578616D706C652E636F6D';
        $res = 'example.com';

        $this->assertEquals($res, Utilities::convertHexToString($hex));
    }
}
