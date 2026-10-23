<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Builds the generated reference files of the agent skill from the code, so
 * they cannot drift from it: transactions.md from the transaction models and
 * TRANSACTION_FORMATS, flags.md from the flag classes, methods.md from the
 * request classes. tests/Skills/SkillReferencesTest.php fails when a file is
 * out of date; run `php skills/build.php` to regenerate.
 */

namespace Hardcastle\XRPL_PHP\Skill;

use ReflectionClass;
use ReflectionNamedType;

require_once __DIR__ . '/../vendor/autoload.php';

const SRC = __DIR__ . '/../src';
const REFERENCES = __DIR__ . '/xrpl-php/references';

/**
 * The generated files, path => content.
 *
 * @return array<string, string>
 */
function references(): array
{
    return [
        REFERENCES . '/transactions.md' => transactions(),
        REFERENCES . '/flags.md' => flags(),
        REFERENCES . '/methods.md' => methods(),
    ];
}

/**
 * The part of a fully qualified name after the last backslash.
 */
function shortName(string $name): string
{
    $position = strrpos($name, '\\');

    return $position === false ? $name : substr($name, $position + 1);
}

/**
 * A property map's value as the codec class it names, short.
 */
function typeName(mixed $typeClass): string
{
    return shortName((string) $typeClass);
}

function definitions(): array
{
    return json_decode(
        (string) file_get_contents(SRC . '/Core/RippleBinaryCodec/Definitions/definitions.json'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
}

/**
 * The classes a directory holds, by PSR-4, sorted.
 *
 * @return list<class-string>
 */
function classesIn(string $directory, string $namespace): array
{
    $classes = [];
    $files = glob($directory . '/*.php');
    foreach ($files === false ? [] : $files as $file) {
        $class = $namespace . '\\' . basename($file, '.php');
        if (class_exists($class)) {
            $classes[] = $class;
        }
    }
    sort($classes);

    return $classes;
}

/**
 * The default value of a property, as the array it is declared as.
 *
 * @param class-string $class
 * @return array<string, mixed>
 */
function defaultArray(string $class, string $property): array
{
    $defaults = (new ReflectionClass($class))->getDefaultProperties();
    $value = $defaults[$property] ?? [];

    /** @var array<string, mixed> */
    return is_array($value) ? $value : [];
}

function transactions(): string
{
    $definitions = definitions();
    $formats = $definitions['TRANSACTION_FORMATS'];
    $optionality = [0 => 'required', 1 => 'optional', 2 => 'default'];
    $namespace = 'Hardcastle\\XRPL_PHP\\Models\\Transaction\\TransactionTypes';

    $out = "# Transaction models\n\n";
    $out .= "Generated from the model classes and `TRANSACTION_FORMATS` in `definitions.json` "
        . "(rippled 3.4.0). Every model extends `{$namespace}\\BaseTransaction`, takes the "
        . "transaction as an array in its constructor, validates the `TransactionType` against "
        . "the class, and gives the array back through `toArray()`. A plain array with the same "
        . "keys works everywhere a model does; the models exist for type checking and "
        . "discoverability.\n\n";
    $out .= "```php\nuse {$namespace}\\Payment;\n\n\$payment = new Payment([\n"
        . "    'TransactionType' => 'Payment',\n    'Account' => \$wallet->getAddress(),\n"
        . "    'Destination' => \$destination,\n    'Amount' => '1000000',\n]);\n"
        . "\$signed = \$wallet->sign(\$client->autofill(\$payment));\n```\n\n";
    $out .= "\"Required\" is what rippled's format says; `Sequence`, `Fee`, `LastLedgerSequence` "
        . "and `SigningPubKey` are required too but come from `autofill()` and `sign()`. "
        . "Type is the codec class the field is serialized with: `Amount` takes drops as a "
        . "string, an IOU as `['currency','issuer','value']` or an MPT as "
        . "`['mpt_issuance_id','value']`; `AccountId` an r-address; `Hash256` 64 hex "
        . "characters; `Blob` hex; `UnsignedInt*` an integer; `StArray` a list of "
        . "single-key objects.\n\n";

    $out .= "## Common fields (every type)\n\n| Field | Type | |\n|---|---|---|\n";
    $baseClass = $namespace . '\\BaseTransaction';
    if (!class_exists($baseClass)) {
        throw new \RuntimeException("{$baseClass} not found");
    }
    $base = defaultArray($baseClass, 'baseProperties');
    $commonOptionality = [];
    foreach ($formats['common'] as $field) {
        $commonOptionality[$field['name']] = $optionality[$field['optionality']] ?? '';
    }
    foreach ($base as $name => $type) {
        $out .= "| `{$name}` | `" . typeName($type) . "` | " . ($commonOptionality[$name] ?? '') . " |\n";
    }

    $modelled = [];
    foreach (classesIn(SRC . '/Models/Transaction/TransactionTypes', $namespace) as $class) {
        $type = shortName($class);
        if ($type === 'BaseTransaction') {
            continue;
        }
        $modelled[] = $type;
        $properties = defaultArray($class, 'transactionTypeProperties');
        $required = [];
        foreach ($formats[$type] ?? [] as $field) {
            $required[$field['name']] = $optionality[$field['optionality']] ?? '';
        }
        $out .= "\n## {$type}\n\n`{$class}`\n\n";
        if ($properties === []) {
            $out .= "No fields beyond the common ones.\n";
            continue;
        }
        $out .= "| Field | Type | |\n|---|---|---|\n";
        foreach ($properties as $name => $typeClass) {
            $out .= "| `{$name}` | `" . typeName($typeClass) . "` | " . ($required[$name] ?? '') . " |\n";
        }
    }

    $withoutModel = array_diff(array_keys($formats), $modelled, ['common']);
    sort($withoutModel);
    $out .= "\n## Types without a model\n\nThe codec encodes and decodes these (the definitions know "
        . "them), but no model class exists yet, either because the amendment is not active on "
        . "Mainnet (Vault, Loan, Sponsorship, Confidential MPT) or because the type is a "
        . "pseudo-transaction only validators send. Use a plain array if you need one.\n\n"
        . implode(', ', array_map(fn(string $t): string => "`{$t}`", $withoutModel)) . "\n";

    $out .= "\n## Xahau\n\nThe Xahau transaction types (`SetHook`, `Invoke`, `URIToken*`, `Remit`, ...) "
        . "are in the `hardcastle/xahau_php` package, together with Xahau's own definitions; "
        . "this library has none of them since 3.0.0.\n";

    return $out;
}

function flags(): string
{
    $out = "# Flag constants\n\n";
    $out .= "Generated from the flag classes. Values are those of rippled 3.4.0. Transaction "
        . "flags are combined with `|` into the `Flags` field; `AccountSetAsfFlags` values go "
        . "one at a time into `SetFlag` or `ClearFlag`; ledger entry flags are read from the "
        . "`Flags` of an object the ledger returned. Every class has `has(\$flags, \$flag)`, "
        . "`parse(\$flags)` (names of the flags set) and `all()`.\n\n"
        . "```php\nuse Hardcastle\\XRPL_PHP\\Models\\Transaction\\Flags\\OfferCreateFlags;\n"
        . "use Hardcastle\\XRPL_PHP\\Models\\Ledger\\Flags\\AccountRootFlags;\n\n"
        . "'Flags' => OfferCreateFlags::tfSell | OfferCreateFlags::tfFillOrKill,\n"
        . "AccountRootFlags::has(\$accountData['Flags'], AccountRootFlags::lsfRequireDestTag);\n"
        . "AccountRootFlags::parse(\$accountData['Flags']); // ['lsfRequireDestTag', ...]\n```\n";

    foreach ([
        'Transaction flags' => [SRC . '/Models/Transaction/Flags', 'Hardcastle\\XRPL_PHP\\Models\\Transaction\\Flags'],
        'Ledger entry flags' => [SRC . '/Models/Ledger/Flags', 'Hardcastle\\XRPL_PHP\\Models\\Ledger\\Flags'],
    ] as $title => [$directory, $namespace]) {
        $out .= "\n# {$title}\n";
        foreach (classesIn($directory, $namespace) as $class) {
            $reflection = new ReflectionClass($class);
            $out .= "\n## " . $reflection->getShortName() . "\n\n`{$class}`\n\n| Constant | Value | |\n|---|---|---|\n";
            foreach ($reflection->getReflectionConstants() as $constant) {
                $doc = $constant->getDocComment();
                $description = $doc === false ? '' : trim(preg_replace('/^\/\*\*\s*|\s*\*\/$/', '', $doc) ?? '');
                $value = $constant->getValue();
                $out .= sprintf("| `%s` | `0x%08X` | %s |\n", $constant->getName(), is_int($value) ? $value : 0, $description);
            }
        }
    }

    return $out;
}

function methods(): string
{
    $out = "# Request classes\n\n";
    $out .= "Generated from the request classes. Each one maps to a rippled API method; its "
        . "constructor parameters are the method's params in camelCase (sent in snake_case). "
        . "Pass an instance to `JsonRpcClient::syncRequest()` for a response object, or to "
        . "`request()` for a promise; anything not covered goes through `rawSyncRequest()`.\n\n"
        . "```php\nuse Hardcastle\\XRPL_PHP\\Models\\Account\\AccountInfoRequest;\n\n"
        . "\$response = \$client->syncRequest(new AccountInfoRequest(account: \$address, ledgerIndex: 'validated'));\n"
        . "\$accountData = \$response->getResult()['account_data'];\n```\n\n"
        . "`syncRequest()` returns the matching `*Response` (with `getResult()`, `getStatus()`, "
        . "`getWarnings()`) or an `ErrorResponse` (with `getError()`, `getStatusCode()`); check "
        . "with `instanceof` before reading the result.\n";

    $directories = glob(SRC . '/Models/*', GLOB_ONLYDIR);
    foreach ($directories === false ? [] : $directories as $directory) {
        $group = basename($directory);
        $namespace = 'Hardcastle\\XRPL_PHP\\Models\\' . $group;
        $requests = array_filter(classesIn($directory, $namespace), fn(string $c): bool => str_ends_with($c, 'Request'));
        if ($requests === []) {
            continue;
        }
        $out .= "\n# {$group}\n";
        foreach ($requests as $class) {
            $reflection = new ReflectionClass($class);
            $command = (string) ($reflection->getDefaultProperties()['command'] ?? '');
            $out .= "\n## " . $reflection->getShortName() . " (`{$command}`)\n\n`{$class}`\n\n";
            $constructor = $reflection->getConstructor();
            $parameters = $constructor === null ? [] : $constructor->getParameters();
            if ($parameters === []) {
                $out .= "No parameters.\n";
                continue;
            }
            $out .= "| Parameter | Type | Default |\n|---|---|---|\n";
            foreach ($parameters as $parameter) {
                $type = $parameter->getType();
                $typeName = $type instanceof ReflectionNamedType ? ($type->allowsNull() && $type->getName() !== 'mixed' ? '?' : '') . $type->getName() : (string) $type;
                $default = $parameter->isDefaultValueAvailable() ? var_export($parameter->getDefaultValue(), true) : '';
                $out .= "| `\${$parameter->getName()}` | `{$typeName}` | " . ($default === '' ? 'required' : "`{$default}`") . " |\n";
            }
        }
    }

    return $out;
}

if (isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    foreach (references() as $path => $content) {
        file_put_contents($path, $content);
        echo basename($path) . ': ' . strlen($content) . " bytes\n";
    }
}
