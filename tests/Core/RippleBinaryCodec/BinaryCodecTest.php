<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

class BinaryCodecTest extends TestCase
{
    public const SIMPLE_JSON = ["CloseResolution" => 1,  "Method" => 2];
    public const SINGLE_LEVEL_OBJECT_JSON = "{\"Memo\":{\"Memo\":{\"Method\":2}}}";
    public const MULTI_LEVEL_OBJECT_JSON = "{\"Memo\":{\"Memo\":{\"CloseResolution\":1,\"Method\":2}}}";

    public const SIMPLE_HEX = "011001021002";
    public const SINGLE_OBJECT_HEX = "EAEA021002E1E1";
    public const MULTI_LEVEL_OBJECT_HEX = "EAEA011001021002E1E1";

    private BinaryCodec $binaryCodec;

    protected function setUp(): void
    {
        $raw = file_get_contents("/app/tests/Core/RippleBinaryCodec/fixtures.json"); //TODO: use proper path
        $this->fixtures = json_decode($raw, true);

        $this->binaryCodec = new BinaryCodec();
    }

    //https://github.com/XRPLF/xrpl4j/blob/main/xrpl4j-binary-codec/src/test/java/org/xrpl/xrpl4j/codec/binary/XrplBinaryCodecTest.java


    public function testEncodeDecodeSimple(): void
    {
        $this->assertEquals(
            self::SIMPLE_HEX,
            $this->binaryCodec->encode(self::SIMPLE_JSON)
        );

        $this->assertEquals(
            self::SIMPLE_JSON,
            $this->binaryCodec->decode(self::SIMPLE_HEX)
        );
    }

}