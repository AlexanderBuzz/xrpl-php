<?php declare(strict_types=1);

namespace XRPL_PHP\Test\Core\RippleBinaryCodec\Types;

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Path;

final class PathTest extends TestCase
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private array $json;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private string $hex;

    protected function setUp(): void
    {
        $this->json = [
            [
                "account" => "r9hEDb4xBGRfBCcX3E4FirDWQBAYtpxC8K",
                "currency" => "BTC",
                "issuer" => "r9hEDb4xBGRfBCcX3E4FirDWQBAYtpxC8K",
            ],
            [
                "account" => "r3AWbdp2jQLXLywJypdoNwVSvr81xs3uhn",
                "currency" => "BTC",
                "issuer" => "r3AWbdp2jQLXLywJypdoNwVSvr81xs3uhn",
            ],
            [
                "currency" => "XRP"
            ],
            [
                "currency" => "USD",
                "issuer" => "rvYAfWj5gh67oV6fW32ZzP3Aw4Eubs59B",
            ],
        ];

        $this->hex = "31585E1F3BD02A15D6185F8BB9B57CC60DEDDB37C10000000000000000000000004254430000000000585E1F3BD02A15D6185F8BB9B57CC60DEDDB37C13157180C769B66D942EE69E6DCC940CA48D82337AD000000000000000000000000425443000000000057180C769B66D942EE69E6DCC940CA48D82337AD1000000000000000000000000000000000000000003000000000000000000000000055534400000000000A20B3C85F482532A9578DBB3950B85CA06594D1";
    }


    public function testDecode(): void
    {
        $this->assertEquals(
            $this->json,
            Path::fromHex($this->hex)->toJson()
        );
    }

    public function testEncode(): void
    {
        $serializedJson = json_encode($this->json);
        $this->assertEquals(
            $this->hex,
            Path::fromJson($serializedJson)->toHex()
        );
    }
}