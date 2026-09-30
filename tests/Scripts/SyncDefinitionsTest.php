<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Scripts;

use PHPUnit\Framework\TestCase;
use function Hardcastle\XRPL_PHP\Scripts\SyncDefinitions\normalize;
use function Hardcastle\XRPL_PHP\Scripts\SyncDefinitions\summarize;

require_once __DIR__ . '/../../scripts/sync-definitions.php';

/**
 * scripts/sync-definitions.php writes a node's server_definitions result as
 * the bundled file. Its formatting has to reproduce the committed file from
 * the committed content, or every run would rewrite the file without a
 * change in substance.
 */
final class SyncDefinitionsTest extends TestCase
{
    private const DEFINITIONS_PATH = __DIR__
        . '/../../src/Core/RippleBinaryCodec/Definitions/definitions.json';

    public function testNormalizeReproducesTheBundledFile(): void
    {
        $committed = (string) file_get_contents(self::DEFINITIONS_PATH);
        $result = json_decode($committed, true, 512, JSON_THROW_ON_ERROR);
        $result['status'] = 'success';          // what the RPC envelope adds

        $this->assertSame($committed, normalize($result));
    }

    public function testNormalizeRejectsWhatIsNotADefinitionsResult(): void
    {
        $this->expectException(\RuntimeException::class);

        normalize(['error' => 'unknownCmd']);
    }

    public function testSummarizeNamesWhatChanged(): void
    {
        $old = ['TRANSACTION_TYPES' => ['Payment' => 0], 'FIELDS' => [['Account', []], ['Gone', []]]];
        $new = ['TRANSACTION_TYPES' => ['Payment' => 0, 'VaultCreate' => 59], 'FIELDS' => [['Account', []]]];

        $this->assertSame(
            [
                'TRANSACTION_TYPES         1 ->    2  added: VaultCreate',
                'FIELDS                    2 ->    1  removed: Gone',
            ],
            summarize($old, $new)
        );
        $this->assertSame([], summarize($new, $new));
    }
}
