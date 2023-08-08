<?php declare(strict_types=1);

namespace XRPL_PHP\Test\Core\RippleBinaryCodec\Types;

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Blob;
use XRPL_PHP\Core\RippleBinaryCodec\Types\StArray;
use XRPL_PHP\Core\RippleBinaryCodec\Types\StObject;

/**
 *
 */
final class StObjectTest extends TestCase
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private array $fixtures;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private array $json;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private string $hex;

    protected function setUp(): void
    {
        $raw = file_get_contents(__DIR__ . "/fixtures.json"); //TODO: use proper path
        $this->fixtures = json_decode($raw, true);

        $this->json = ["Memo" => $this->fixtures['Memo']];
        $this->hex = $this->fixtures['MemoHex'];

        parent::setUp();
    }

    public function testDecodeStObject(): void
    {
        $this->assertEquals(
            $this->json,
            StObject::fromHex($this->hex)->toJson()
        );

    }

    public function testEncodeStObject(): void
    {
        $this->assertEquals(
            $this->hex,
            StObject::fromJson(json_encode($this->json))->toString()
        );
    }
}