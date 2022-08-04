<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleAddressCodec\AddressCodec;
use XRPL_PHP\Core\RippleAddressCodec\Utils;


/**
 * XRPL4J:
 * https://github.com/XRPLF/xrpl4j/blob/main/xrpl4j-address-codec/src/test/java/org/xrpl/xrpl4j/codec/addresses/AddressCodecTest.java
 *
 * XRPL.JS
 *
 */
class AddressCodecTest extends TestCase
{
    private AddressCodec $addressCodec;

    public function setUp(): void
    {
        $this->addressCodec = new AddressCodec();
    }

    /*
    public function testEncodeXAddress(): void
    {

    }
    */

    public function testEncodeEd25519Seed(): void
    {
        $encoded = $this->addressCodec->encodeSeed(
            Buffer::from("4C3A1D213FBDFB14C7C28D609469B341"),
            'ed25519'
        );

        $this->assertEquals(
            "sEdTM1uX8pu2do5XvTnutH6HsouMaM2",
            $encoded
        );
    }

    public function testClassicAddressToXAddress(): void
    {
        $input = 'rGWrZyQqhTp9Xu7G5Pkayo7bXjH4k4QYpf';
        $expected = 'XVLhHMPHU98es4dbozjVtdWzVrDjtV18pX8yuPT7y4xaEHi';

        $this->assertEquals(
            $expected,
            $this->addressCodec->classicAddressToXAddress($input, 4294967295)
        );
    }


    public function testDecodeEd25519Seed(): void
    {
        $expected = "4C3A1D213FBDFB14C7C28D609469B341";

        $seed = "sEdTM1uX8pu2do5XvTnutH6HsouMaM2";
        $decodedSeed = $this->addressCodec->decodeSeed($seed);
        $testValue = strtoupper(Buffer::from($decodedSeed['bytes'])->toString());

        $this->assertEquals($expected, $testValue);
    }
}