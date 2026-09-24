<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Models\Transaction;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Every transaction model has exactly the fields rippled's format for its
 * type lists.
 *
 * definitions.json carries TRANSACTION_FORMATS, the field list per type as
 * the node reports it, with "common" holding the fields every type shares.
 * A model that lacks a field cannot set it (BaseTransaction drops unknown
 * keys), and a model with a field the type does not have would encode
 * something the node rejects - so both directions are checked, and a
 * definitions sync that adds a field to a type fails here until the model
 * follows.
 */
final class TransactionFormatsTest extends TestCase
{
    private const MODELS_DIR = __DIR__ . '/../../../src/Models/Transaction/TransactionTypes';

    private const NAMESPACE = 'Hardcastle\\XRPL_PHP\\Models\\Transaction\\TransactionTypes\\';

    private const DEFINITIONS_PATH = __DIR__
        . '/../../../src/Core/RippleBinaryCodec/Definitions/definitions.json';

    public static function modelProvider(): array
    {
        $cases = [];
        $paths = glob(self::MODELS_DIR . '/*.php');
        foreach ($paths === false ? [] : $paths as $path) {
            $type = basename($path, '.php');
            if ($type === 'BaseTransaction') {
                continue;
            }
            $cases[$type] = [$type];
        }

        return $cases;
    }

    #[DataProvider('modelProvider')]
    public function testModelMatchesFormat(string $type): void
    {
        $raw = json_decode((string) file_get_contents(self::DEFINITIONS_PATH), true, 512, JSON_THROW_ON_ERROR);
        $formats = $raw['TRANSACTION_FORMATS'];

        $this->assertArrayHasKey($type, $formats, "{$type} is not a transaction type rippled knows");

        $common = array_column($formats['common'], 'name');
        $expected = array_diff(array_column($formats[$type], 'name'), $common, ['TransactionType']);

        /** @var class-string $class */
        $class = self::NAMESPACE . $type;
        $defaults = (new ReflectionClass($class))->getDefaultProperties();
        $actual = array_keys($defaults['transactionTypeProperties']);

        $this->assertSame([], array_values(array_diff($expected, $actual)), "{$type} lacks fields its format has");
        $this->assertSame([], array_values(array_diff($actual, $expected)), "{$type} has fields its format lacks");
    }
}
