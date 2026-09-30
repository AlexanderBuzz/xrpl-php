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
 * Refreshes src/Core/RippleBinaryCodec/Definitions/definitions.json from a
 * node's server_definitions response.
 *
 * The bundled file is a verbatim snapshot of what a node reports, so this
 * script never edits entries: it unwraps the JSON-RPC result, drops the
 * request-scoped "status" key and writes the rest with sorted top-level keys
 * and two-space indentation. The node is the only source that cannot run
 * ahead of a release; ripple-binary-codec's main branch does.
 *
 * Usage:
 *   php scripts/sync-definitions.php                       # s1.ripple.com (Mainnet)
 *   php scripts/sync-definitions.php --node https://s.devnet.rippletest.net:51234/
 *   php scripts/sync-definitions.php --check               # exit 1 if the file is behind, write nothing
 */

namespace Hardcastle\XRPL_PHP\Scripts\SyncDefinitions;

const DEFAULT_NODE = 'https://s1.ripple.com:51234/';
const DEFINITIONS_FILE = __DIR__ . '/../src/Core/RippleBinaryCodec/Definitions/definitions.json';
const SECTIONS = ['TYPES', 'LEDGER_ENTRY_TYPES', 'TRANSACTION_TYPES', 'TRANSACTION_RESULTS', 'FIELDS',
    'TRANSACTION_FLAGS', 'ACCOUNT_SET_FLAGS', 'LEDGER_ENTRY_FLAGS', 'TRANSACTION_FORMATS', 'LEDGER_ENTRY_FORMATS'];

/**
 * A JSON-RPC call with empty params; returns the decoded "result".
 *
 * @return array<string, mixed>
 */
function rpc(string $nodeUrl, string $method): array
{
    $context = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => json_encode(['method' => $method, 'params' => [(object) []]], JSON_THROW_ON_ERROR),
        'timeout' => 60,
        'ignore_errors' => true,
    ]]);
    $body = @file_get_contents($nodeUrl, false, $context);
    if ($body === false) {
        throw new \RuntimeException("No response from {$nodeUrl} for {$method}");
    }
    $decoded = json_decode($body, true);
    if (!is_array($decoded) || !isset($decoded['result']) || !is_array($decoded['result'])) {
        throw new \RuntimeException("Unexpected {$method} response from {$nodeUrl}: " . substr($body, 0, 300));
    }

    /** @var array<string, mixed> */
    return $decoded['result'];
}

/**
 * The file content for a server_definitions result: the request-scoped
 * "status" dropped, top-level keys sorted, two-space indent, trailing
 * newline. Entries are never touched.
 *
 * @param array<string, mixed> $result
 */
function normalize(array $result): string
{
    if (!isset($result['FIELDS'], $result['TYPES'], $result['hash'])) {
        throw new \RuntimeException('Not a server_definitions result: FIELDS, TYPES or hash missing');
    }
    unset($result['status']);
    ksort($result, SORT_STRING);

    $json = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    // PHP indents with four spaces; the file, like every other SDK's, uses two.
    $json = preg_replace_callback(
        '/^(?: {4})+/m',
        static fn(array $match): string => str_repeat(' ', intdiv(strlen($match[0]), 2)),
        $json
    );

    return $json . "\n";
}

/**
 * Per section, the entries one side has and the other lacks.
 *
 * @param array<string, mixed> $old
 * @param array<string, mixed> $new
 * @return list<string>
 */
function summarize(array $old, array $new): array
{
    $lines = [];
    foreach (SECTIONS as $section) {
        $a = keysOf($old[$section] ?? [], $section);
        $b = keysOf($new[$section] ?? [], $section);
        $added = array_values(array_diff($b, $a));
        $removed = array_values(array_diff($a, $b));
        if ($added === [] && $removed === [] && count($a) === count($b)) {
            continue;
        }
        $line = sprintf('%-22s %4d -> %4d', $section, count($a), count($b));
        if ($added !== []) {
            $line .= '  added: ' . implode(', ', $added);
        }
        if ($removed !== []) {
            $line .= '  removed: ' . implode(', ', $removed);
        }
        $lines[] = $line;
    }

    return $lines;
}

/**
 * The names a section's entries carry: keys for the keyed sections, the
 * first element for FIELDS.
 *
 * @return list<string>
 */
function keysOf(mixed $section, string $name): array
{
    if (!is_array($section)) {
        return [];
    }
    if ($name === 'FIELDS') {
        $names = [];
        foreach ($section as $entry) {
            if (is_array($entry) && isset($entry[0]) && is_string($entry[0])) {
                $names[] = $entry[0];
            }
        }

        return $names;
    }

    return array_map('strval', array_keys($section));
}

/**
 * @param list<string> $argv
 */
function main(array $argv): int
{
    $fromEnvironment = getenv('NODE_URL');
    $nodeUrl = is_string($fromEnvironment) && $fromEnvironment !== '' ? $fromEnvironment : DEFAULT_NODE;
    $check = false;
    for ($i = 1; $i < count($argv); $i++) {
        switch ($argv[$i]) {
            case '--node':
                $nodeUrl = $argv[++$i] ?? '';
                break;
            case '--check':
                $check = true;
                break;
            case '-h':
            case '--help':
                echo "Usage: php scripts/sync-definitions.php [--node URL] [--check]\n";
                return 0;
            default:
                fwrite(STDERR, "Unknown argument: {$argv[$i]}\n");
                return 1;
        }
    }
    if ($nodeUrl === '') {
        fwrite(STDERR, "--node needs a URL\n");
        return 1;
    }

    echo "Fetching server_definitions from {$nodeUrl}...\n";
    $result = rpc($nodeUrl, 'server_definitions');
    $content = normalize($result);

    try {
        // rippled reports build_version; a Clio front end reports the
        // libxrpl_version it was built against instead.
        $info = rpc($nodeUrl, 'server_info')['info'] ?? [];
        $version = 'unknown';
        if (is_array($info)) {
            foreach (['build_version', 'rippled_version', 'libxrpl_version'] as $key) {
                if (is_string($info[$key] ?? null)) {
                    $version = $info[$key] . ($key === 'libxrpl_version' ? ' (libxrpl, via Clio)' : '');
                    break;
                }
            }
        }
    } catch (\RuntimeException) {
        $version = 'unknown';
    }
    echo "Node version: {$version}\n";

    $current = is_file(DEFINITIONS_FILE) ? (string) file_get_contents(DEFINITIONS_FILE) : '';
    $old = $current === '' ? [] : (array) json_decode($current, true);
    $oldHash = is_string($old['hash'] ?? null) ? $old['hash'] : 'none';
    $newHash = (string) $result['hash'];

    if ($content === $current) {
        echo "Definitions unchanged (hash {$newHash}).\n";
        return 0;
    }

    echo "Definitions differ: {$oldHash} -> {$newHash}\n";
    foreach (summarize($old, $result) as $line) {
        echo "  {$line}\n";
    }
    if ($check) {
        echo "Not written (--check).\n";
        return 1;
    }

    file_put_contents(DEFINITIONS_FILE, $content);
    echo "Written to " . DEFINITIONS_FILE . "\n";
    echo "Next: php skills/build.php; vendor/bin/phpunit --exclude-group integration; then update the parity test and CHANGELOG.\n";

    return 0;
}

if (isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    /** @var list<string> $argv */
    exit(main($argv));
}
