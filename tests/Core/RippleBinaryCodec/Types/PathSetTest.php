<?php declare(strict_types=1);

namespace XRPL_PHP\Test\Core\RippleBinaryCodec\Types;

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\RippleBinaryCodec\Types\PathSet;

final class PathSetTest extends TestCase
{
    private array $json = [
        [
            [
                "account" => "r9hEDb4xBGRfBCcX3E4FirDWQBAYtpxC8K",
                "currency" => "BTC",
                "issuer" => "r9hEDb4xBGRfBCcX3E4FirDWQBAYtpxC8K",
            ],
            [
                "account" => "rM1oqKtfh1zgjdAgbFmaRm3btfGBX25xVo",
                "currency" => "BTC",
                "issuer" => "rM1oqKtfh1zgjdAgbFmaRm3btfGBX25xVo",
            ],
            [
                "account" => "rvYAfWj5gh67oV6fW32ZzP3Aw4Eubs59B",
                "currency" => "BTC",
                "issuer" => "rvYAfWj5gh67oV6fW32ZzP3Aw4Eubs59B",
            ],
            [
                "currency" => "USD",
                "issuer" => "rvYAfWj5gh67oV6fW32ZzP3Aw4Eubs59B",
            ],
        ],
        [
            [
                "account" => "r9hEDb4xBGRfBCcX3E4FirDWQBAYtpxC8K",
                "currency" => "BTC",
                "issuer" => "r9hEDb4xBGRfBCcX3E4FirDWQBAYtpxC8K",
            ],
            [
                "account" => "rM1oqKtfh1zgjdAgbFmaRm3btfGBX25xVo",
                "currency" => "BTC",
                "issuer" => "rM1oqKtfh1zgjdAgbFmaRm3btfGBX25xVo",
            ],
            [
                "account" => "rpvfJ4mR6QQAeogpXEKnuyGBx8mYCSnYZi",
                "currency" => "BTC",
                "issuer" => "rpvfJ4mR6QQAeogpXEKnuyGBx8mYCSnYZi",
            ],
            [
                "currency" => "USD",
                "issuer" => "rvYAfWj5gh67oV6fW32ZzP3Aw4Eubs59B",
            ],
        ],
        [
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
            ["currency" => "XRP"],
            [
                "currency" => "USD",
                "issuer" => "rvYAfWj5gh67oV6fW32ZzP3Aw4Eubs59B",
            ]
        ]
    ];

    private string $hex = "31585E1F3BD02A15D6185F8BB9B57CC60DEDDB37C10000000000000000000000004254430000000000585E1F3BD02A15D6185F8BB9B57CC60DEDDB37C131E4FE687C90257D3D2D694C8531CDEECBE84F33670000000000000000000000004254430000000000E4FE687C90257D3D2D694C8531CDEECBE84F3367310A20B3C85F482532A9578DBB3950B85CA06594D100000000000000000000000042544300000000000A20B3C85F482532A9578DBB3950B85CA06594D13000000000000000000000000055534400000000000A20B3C85F482532A9578DBB3950B85CA06594D1FF31585E1F3BD02A15D6185F8BB9B57CC60DEDDB37C10000000000000000000000004254430000000000585E1F3BD02A15D6185F8BB9B57CC60DEDDB37C131E4FE687C90257D3D2D694C8531CDEECBE84F33670000000000000000000000004254430000000000E4FE687C90257D3D2D694C8531CDEECBE84F33673115036E2D3F5437A83E5AC3CAEE34FF2C21DEB618000000000000000000000000425443000000000015036E2D3F5437A83E5AC3CAEE34FF2C21DEB6183000000000000000000000000055534400000000000A20B3C85F482532A9578DBB3950B85CA06594D1FF31585E1F3BD02A15D6185F8BB9B57CC60DEDDB37C10000000000000000000000004254430000000000585E1F3BD02A15D6185F8BB9B57CC60DEDDB37C13157180C769B66D942EE69E6DCC940CA48D82337AD000000000000000000000000425443000000000057180C769B66D942EE69E6DCC940CA48D82337AD1000000000000000000000000000000000000000003000000000000000000000000055534400000000000A20B3C85F482532A9578DBB3950B85CA06594D100";

    public function testDecode(): void
    {
        $this->assertEquals(
            $this->json,
            PathSet::fromHex($this->hex)->toJson()
        );
    }

    public function testEncode(): void
    {
        $serializedJson = json_encode($this->json);
        $this->assertEquals(
            $this->hex,
            PathSet::fromJson($serializedJson)->toHex()
        );
    }
}