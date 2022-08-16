<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Blob;
use XRPL_PHP\Core\RippleBinaryCodec\Types\StArray;
use XRPL_PHP\Core\RippleBinaryCodec\Types\StObject;

/**
 *
 */
final class StObjectTest extends TestCase
{
    private array $fixtures;

    private array $json;

    private string $hex;

    protected function setUp(): void
    {
        $raw = file_get_contents("/app/tests/Core/RippleBinaryCodec/Types/fixtures.json"); //TODO: use proper path
        $this->fixtures = json_decode($raw, true);

        $this->json = ["Memo" => $this->fixtures['Memo']];
        $this->hex = $this->fixtures['MemoHex'];

        parent::setUp();
    }

    public function testDecode()
    {
        $this->assertEquals(
            $this->json,
            StObject::fromHex($this->hex)->toJson()
        );

    }

    public function testEncode()
    {
        $this->assertEquals(
            $this->hex,
            StObject::fromJson(json_encode($this->json))->toString()
        );
    }
}