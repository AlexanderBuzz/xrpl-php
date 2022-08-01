<?php declare(strict_types=1);

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

    private string $json;

    private string $hex;

    private Vector256 $vector256;

    protected function setUp(): void
    {
        $this->json = json_encode([self::VALUE1, self::VALUE2]);
        $this->hex = self::VALUE1 . self::VALUE2;
        $this->vector256 = new Vector256();
    }

   public function testDecode()
   {
       $this->assertEquals(
           $this->json,
           $this->vector256->fromHex($this->hex)->toJson()
       );
   }

    public function testEncode()
    {
        $this->assertEquals(
            $this->hex,
            $this->vector256->fromSerializedJson($this->json)->toHex()
        );
    }
}