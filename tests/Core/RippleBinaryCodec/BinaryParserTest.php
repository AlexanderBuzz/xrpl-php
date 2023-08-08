<?php declare(strict_types=1);

namespace XRPL_PHP\Test\Core\RippleBinaryCodec;

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\RippleBinaryCodec\Serdes\BinaryParser;

final class BinaryParserTest extends TestCase
{
    public const SIMPLE_JSON = "{\"CloseResolution\":1,\"Method\":2}";
    public const SINGLE_LEVEL_OBJECT_JSON = "{\"Memo\":{\"Memo\":{\"Method\":2}}}";
    public const MULTI_LEVEL_OBJECT_JSON = "{\"Memo\":{\"Memo\":{\"CloseResolution\":1,\"Method\":2}}}";
    public const SIMPLE_HEX = "011001021002";
    public const SINGLE_OBJECT_HEX = "EAEA021002E1E1";
    public const MULTI_LEVEL_OBJECT_HEX = "EAEA011001021002E1E1";

    /** @psalm-suppress PropertyNotSetInConstructor */
    private array $fixtures;

    protected function setUp(): void
    {
        $raw = file_get_contents(__DIR__ . "/fixtures.json");
        $this->fixtures = json_decode($raw, true);
    }

    //https://github.com/XRPLF/xrpl.js/blob/main/packages/ripple-binary-codec/test/binary-parser.test.js
    public function testLowLevelApi(): void
    {
        $parser = new BinaryParser($this->fixtures['binary']);

        $this->assertEquals(
            "TransactionType", //TODO: enums?
            $parser->readField()->getName()
        );
    }

    //https://github.com/XRPLF/xrpl4j/blob/main/xrpl4j-binary-codec/src/test/java/org/xrpl/xrpl4j/codec/binary/XrplBinaryCodecTest.java
}