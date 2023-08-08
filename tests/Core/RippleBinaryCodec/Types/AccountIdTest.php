<?php declare(strict_types=1);

namespace XRPL_PHP\Test\Core\RippleBinaryCodec\Types;

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\RippleBinaryCodec\Types\AccountId;

/**
 * XRPL4J:
 * https://github.com/XRPLF/xrpl4j/blob/main/xrpl4j-binary-codec/src/test/java/org/xrpl/xrpl4j/codec/binary/types/AccountIdTypeTest.java
 *
 * XRPL.JS
 *
 */
final class AccountIdTest extends TestCase
{
    private string $json = "r9cZA1mLK5R5Am25ArfXFmqgNwjZgnfk59";

    private string $hex = "5E7B112523F68D2F5E879DB4EAC51C6698A69304";

   public function testDecode(): void
   {
       $this->assertEquals(
           $this->json,
           AccountId::fromHex($this->hex)->toJson()
       );
   }

    public function testEncode(): void
    {
        $this->assertEquals(
            $this->hex,
            AccountId::fromJson($this->json)->toHex()
        );
    }
}