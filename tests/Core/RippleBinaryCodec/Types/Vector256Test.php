<?php declare(strict_types=1);

namespace XRPL_PHP\Test\Core\RippleBinaryCodec\Types;

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Currency;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Vector256;

/**
 * XRPL4J:
 * https://github.com/XRPLF/xrpl4j/blob/main/xrpl4j-binary-codec/src/test/java/org/xrpl/xrpl4j/codec/binary/types/Vector256TypeTest.java
 *
 * XRPL.JS
 * untested...
 */
final class Vector256Test extends TestCase
{
    private const VALUE1 = "42426C4D4F1009EE67080A9B7965B44656D7714D104A72F9B4369F97ABF044EE";

    private const VALUE2 = "4C97EBA926031A7CF7D7B36FDE3ED66DDA5421192D63DE53FFB46E43B9DC8373";

    /** @psalm-suppress PropertyNotSetInConstructor */
    private string $json;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private string $hex;

    protected function setUp(): void
    {
        $this->json = json_encode([self::VALUE1, self::VALUE2]);
        $this->hex = self::VALUE1 . self::VALUE2;
    }

   public function testDecode(): void
   {
       $this->assertEquals(
           $this->json,
           Vector256::fromHex($this->hex)->toJson()
       );
   }

    public function testEncode(): void
    {
        $this->assertEquals(
            $this->hex,
            Vector256::fromJson($this->json)->toHex()
        );
    }
}