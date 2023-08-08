<?php declare(strict_types=1);

namespace XRPL_PHP\Test\Core\RippleAddressCodec;

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleAddressCodec\AddressCodec;

/**
 * XRPL4J:
 * https://github.com/XRPLF/xrpl4j/blob/main/xrpl4j-address-codec/src/test/java/org/xrpl/xrpl4j/codec/addresses/AddressCodecTest.java
 *
 * XRPL.JS
 *
 */
class AddressCodecTest extends TestCase
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private AddressCodec $addressCodec;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private array $fixtures;

    public function setUp(): void
    {
        $this->addressCodec = new AddressCodec();

        //TODO: use relative file path
        $raw = file_get_contents(__DIR__ . "/fixtures.json");
        $this->fixtures = json_decode($raw, true);
    }

    public function testIsValidClassicAddress(): void
    {
        //'isValidClassicAddress - secp256k1 address valid'
        $this->assertTrue($this->addressCodec->isValidClassicAddress('rU6K7V3Po4snVhBBaU29sesqs2qTQJWDw1'));

        //'isValidClassicAddress - ed25519 address valid'
        $this->assertTrue($this->addressCodec->isValidClassicAddress('rLUEXYuLiQptky37CqLcm9USQpPiz5rkpD'));

        //'isValidClassicAddress - invalid'
        $this->assertFalse($this->addressCodec->isValidClassicAddress('rU6K7V3Po4snVhBBaU29sesqs2qTQJWDw2'));

        //'isValidClassicAddress - empty'
        $this->assertFalse($this->addressCodec->isValidClassicAddress(''));
    }

    /*
    public function testEncodeXAddress(): void
    {

    }
    */

    //Ed25519 section

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

    public function testDecodeEd25519Seed(): void
    {
        $seed = "sEdTM1uX8pu2do5XvTnutH6HsouMaM2";
        $decodedSeed = $this->addressCodec->decodeSeed($seed);
        $testValue = strtoupper(Buffer::from($decodedSeed['bytes'])->toString());

        $this->assertEquals(
            "4C3A1D213FBDFB14C7C28D609469B341",
            $testValue
        );
    }

    //Secp256k1 section

    public function testEncodeSecp256k1Seed(): void
    {
        $encoded = $this->addressCodec->encodeSeed(
            Buffer::from('CF2DE378FBDD7E2EE87D486DFB5A7BFF'),
            'secp256k1'
        );

        $this->assertEquals(
            'sn259rEFXrQrWyx3Q7XneWcwV6dfL',
            $encoded
        );
    }

    public function testDecodeSecp256k1Seed(): void
    {
        $seed = 'sn259rEFXrQrWyx3Q7XneWcwV6dfL';
        $decodedSeed = $this->addressCodec->decodeSeed($seed);
        $testValue = strtoupper(Buffer::from($decodedSeed['bytes'])->toString());

        $this->assertEquals(
            'CF2DE378FBDD7E2EE87D486DFB5A7BFF',
            $testValue
        );
    }


    public function testClassicAddressToXAddress(): void
    {
        $classicAddress = 'rGWrZyQqhTp9Xu7G5Pkayo7bXjH4k4QYpf';

        $this->assertEquals(
            'XVLhHMPHU98es4dbozjVtdWzVrDjtV18pX8yuPT7y4xaEHi',
            $this->addressCodec->classicAddressToXAddress($classicAddress, 4294967295)
        );
    }
}