<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Core\RippleBinaryCodec;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;

/**
 * Conformance test against the reference fixtures of ripple-binary-codec.
 *
 * The file codec-fixtures.json is taken verbatim (minus the ledgerData group,
 * which covers ledger headers rather than serialized objects) from
 * https://github.com/XRPLF/xrpl.js/blob/main/packages/ripple-binary-codec/test/fixtures/codec-fixtures.json
 *
 * Every case has to encode to exactly the reference binary and decode back to
 * the reference JSON. Key order is irrelevant, so both sides are normalized.
 */
final class CodecFixturesTest extends TestCase
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private array $fixtures;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private BinaryCodec $binaryCodec;

    protected function setUp(): void
    {
        $raw = file_get_contents(__DIR__ . "/codec-fixtures.json");
        $this->fixtures = json_decode($raw, true);

        $this->binaryCodec = new BinaryCodec();
    }

    public static function transactionProvider(): array
    {
        return self::load('transactions');
    }

    public static function accountStateProvider(): array
    {
        return self::load('accountState');
    }

    #[DataProvider('transactionProvider')]
    public function testTransactionFixture(array $case): void
    {
        $this->assertFixture($case);
    }

    #[DataProvider('accountStateProvider')]
    public function testAccountStateFixture(array $case): void
    {
        $this->assertFixture($case);
    }

    private function assertFixture(array $case): void
    {
        $this->assertEquals(
            strtoupper($case['binary']),
            strtoupper($this->binaryCodec->encode(json_encode($case['json']))),
            'encode'
        );

        $this->assertEquals(
            self::normalize($case['json']),
            self::normalize($this->binaryCodec->decode($case['binary'])),
            'decode'
        );
    }

    private static function load(string $group): array
    {
        $raw = json_decode(file_get_contents(__DIR__ . "/codec-fixtures.json"), true);

        $cases = [];
        foreach ($raw[$group] as $index => $case) {
            $label = $case['json']['TransactionType'] ?? $case['json']['LedgerEntryType'] ?? 'unknown';
            $cases["{$group} #{$index} {$label}"] = [$case];
        }

        return $cases;
    }

    /**
     * Sort object keys recursively so that only the content is compared
     */
    private static function normalize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $normalized = array_map(self::normalize(...), $value);
        if (!array_is_list($normalized)) {
            ksort($normalized);
        }

        return $normalized;
    }
}
