<?php declare(strict_types=1);

namespace XRPL_PHP\Test\Core\RippleBinaryCodec\Types;

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\RippleBinaryCodec\Types\XchainBridge;

final class XchainBridgeTest extends TestCase
{
    private string $hex = "14AF80285F637EE4AF3C20378F9DFB12511ACB8D27000000000000000000000000000000000000000014550FC62003E785DC231A1058A05E56E3F09CF4E60000000000000000000000000000000000000000";

    private array $json = [
        "LockingChainDoor" => "rGzx83BVoqTYbGn7tiVAnFw7cbxjin13jL",
        "LockingChainIssue" => ["currency" => "XRP"],
        "IssuingChainDoor" => "r3kmLJN5D28dHuH8vZNUZpMC43pEHpaocV",
        "IssuingChainIssue" => ["currency" => "XRP"]
    ];

    public function testDecode(): void
    {
        $this->assertEquals(
            $this->json,
            XchainBridge::fromHex($this->hex)->toJson()
        );
    }

    public function testEncode(): void
    {
        $this->assertEquals(
            $this->hex,
            XchainBridge::fromJson(json_encode($this->json))->toHex()
        );
    }
}