<?php declare(strict_types=1);

namespace XRPL_PHP\Test\Core\RippleAddressCodec;

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleAddressCodec\BaseX;
use XRPL_PHP\Core\RippleAddressCodec\Utils;

/**
 * XRPL4J:
 * https://github.com/XRPLF/xrpl4j/blob/main/xrpl4j-address-codec/src/test/java/org/xrpl/xrpl4j/codec/addresses/AddressCodecTest.java
 *
 * XRPL.JS
 *
 */
class BaseXTest extends TestCase
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private BaseX $base58Codec;

    public function setUp(): void
    {
        $this->base58Codec = new BaseX(Utils::XRPL_ALPHABET);
    }

    public function testEncodeDecodeString(): void
    {
        $decoded = array_values(unpack('C*', "Hello World"));
        $encoded = "JxErpTiA7PhnBMd";

        $this->assertEquals(
            $encoded,
            $this->base58Codec->encode(Buffer::from($decoded))
        );
    }
}