<?php declare(strict_types=1);

namespace XRPL_PHP\Test\Core\RippleBinaryCodec\Types;

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Blob;
use XRPL_PHP\Core\RippleBinaryCodec\Types\StArray;
use XRPL_PHP\Core\RippleBinaryCodec\Types\StObject;

final class StArrayTest extends TestCase
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

        $this->json = [
            ["Memo" => $this->fixtures['Memo']],
            ["Memo" => $this->fixtures['Memo']]
        ];
        $this->hex = $this->fixtures['MemoHex'] . $this->fixtures['MemoHex'] . StArray::ARRAY_END_MARKER_HEX;

        parent::setUp();
    }

    public function testDecodeStArray(): void
    {
        $this->assertEquals(
            $this->json,
            StArray::fromHex($this->hex)->toJson()
        );

    }

    public function testEncodeStArray(): void
    {
        $this->assertEquals(
            $this->hex,
            StArray::fromJson(json_encode($this->json))->toString()
        );
    }
}