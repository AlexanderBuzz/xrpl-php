<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Skills;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function Hardcastle\XRPL_PHP\Skill\references;

require_once __DIR__ . '/../../skills/build.php';

/**
 * The agent skill under skills/ has to describe the code as it is.
 *
 * Three reference files are generated from the code by skills/build.php; they
 * have to match what the generator produces now. The hand-written files name
 * classes, functions, methods and constants; every one of them has to exist.
 * And the SKILL.md frontmatter has to name the version the changelog is at.
 */
final class SkillReferencesTest extends TestCase
{
    private const SKILL_DIR = __DIR__ . '/../../skills/xrpl-php';

    private const HAND_WRITTEN = ['SKILL.md', 'references/recipes.md', 'references/troubleshooting.md'];

    public function testGeneratedReferencesAreUpToDate(): void
    {
        foreach (references() as $path => $expected) {
            $this->assertFileExists($path);
            $this->assertSame(
                $expected,
                file_get_contents($path),
                basename($path) . ' is out of date; run `php skills/build.php`'
            );
        }
    }

    public static function handWrittenProvider(): array
    {
        $cases = [];
        foreach (self::HAND_WRITTEN as $file) {
            $cases[$file] = [$file];
        }

        return $cases;
    }

    /**
     * Every fully qualified name under Hardcastle\XRPL_PHP is a class or a
     * function that exists.
     */
    #[DataProvider('handWrittenProvider')]
    public function testEveryQualifiedNameExists(string $file): void
    {
        $text = (string) file_get_contents(self::SKILL_DIR . '/' . $file);
        preg_match_all('/Hardcastle\\\\XRPL_PHP(?:\\\\[A-Za-z0-9_]+)+/', $text, $matches);

        $checked = 0;
        foreach (array_unique($matches[0]) as $name) {
            if (str_ends_with($name, '\\')) {
                continue;
            }
            // A namespace prefix written with a trailing "..." or followed by "\" in prose
            if (class_exists($name) || interface_exists($name) || function_exists($name)) {
                $checked++;
                continue;
            }
            // A namespace named as such (e.g. Hardcastle\XRPL_PHP\Client) is a
            // directory under src/ by PSR-4
            $directory = __DIR__ . '/../../src/' . str_replace('\\', '/', substr($name, strlen('Hardcastle\\XRPL_PHP\\')));
            $this->assertDirectoryExists($directory, "{$file} names {$name}, which is neither a class, a function nor a namespace");
            $checked++;
        }
        $this->assertGreaterThan(0, $checked);
    }

    /**
     * Every Class::member after a `use` import exists as a static method,
     * a constant, or an instance method.
     */
    #[DataProvider('handWrittenProvider')]
    public function testEveryImportedMemberExists(string $file): void
    {
        $text = (string) file_get_contents(self::SKILL_DIR . '/' . $file);

        preg_match_all('/^use (Hardcastle\\\\XRPL_PHP\\\\[A-Za-z0-9_\\\\]+);/m', $text, $uses);
        /** @var array<string, class-string> $imports */
        $imports = [];
        foreach ($uses[1] as $fqcn) {
            if (!class_exists($fqcn)) {
                $this->fail("{$file} imports {$fqcn}, which does not exist");
            }
            $position = strrpos($fqcn, '\\');
            $imports[$position === false ? $fqcn : substr($fqcn, $position + 1)] = $fqcn;
        }

        preg_match_all('/\b([A-Z][A-Za-z0-9]+)::([A-Za-z_][A-Za-z0-9_]*)/', $text, $members, PREG_SET_ORDER);
        $checked = 0;
        foreach ($members as [, $short, $member]) {
            if (!isset($imports[$short])) {
                continue;
            }
            $class = $imports[$short];
            $this->assertTrue(
                method_exists($class, $member) || defined("{$class}::{$member}"),
                "{$file} names {$short}::{$member}, which {$class} does not have"
            );
            $checked++;
        }
        if ($imports !== []) {
            $this->assertGreaterThan(0, $checked, "{$file} imports classes but uses none of their members");
        } else {
            $this->assertSame(0, $checked, "{$file} has no imports, so no member could be checked");
        }
    }

    /**
     * Every `->method(` on $client and $wallet exists on the class.
     */
    #[DataProvider('handWrittenProvider')]
    public function testEveryClientAndWalletCallExists(string $file): void
    {
        $text = (string) file_get_contents(self::SKILL_DIR . '/' . $file);
        $classes = [
            'client' => \Hardcastle\XRPL_PHP\Client\JsonRpcClient::class,
            'wallet' => \Hardcastle\XRPL_PHP\Wallet\Wallet::class,
            'codec' => \Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec::class,
        ];

        $found = preg_match_all('/\$(client|wallet|codec)->([A-Za-z_][A-Za-z0-9_]*)\(/', $text, $calls, PREG_SET_ORDER);
        $this->assertNotFalse($found, 'the pattern has to compile');
        foreach ($calls as [, $variable, $method]) {
            $this->assertTrue(
                method_exists($classes[$variable], $method),
                "{$file} calls \${$variable}->{$method}(), which {$classes[$variable]} does not have"
            );
        }
    }

    /**
     * The internal skills under .agents/skills name files, directories and
     * test classes of this repository; every one of them has to exist, or the
     * procedure sends an agent to a place that is not there.
     */
    public function testInternalSkillsNameExistingPaths(): void
    {
        $root = realpath(__DIR__ . '/../..');
        $this->assertNotFalse($root);
        $files = glob($root . '/.agents/skills/{sync-definitions,release,xrpl-review}/{SKILL.md,prompts/*.md}', GLOB_BRACE);
        $this->assertNotFalse($files);
        $this->assertNotSame([], $files);

        $checked = 0;
        foreach ($files as $file) {
            $text = (string) file_get_contents($file);
            preg_match_all('/`((?:src|tests|skills|scripts|examples|docs|\\.agents|\.claude|\.claude-plugin)\/[A-Za-z0-9_.\/-]+?)(?:\*\*|\*)?`/', $text, $paths);
            foreach (array_unique($paths[1]) as $path) {
                $path = rtrim($path, '/');
                $this->assertTrue(
                    file_exists($root . '/' . $path),
                    basename(dirname($file)) . '/' . basename($file) . " names {$path}, which does not exist"
                );
                $checked++;
            }
            // A placeholder like Rippled<version>ParityTest is not a class name
            preg_match_all('/(?<![>\w])([A-Z][A-Za-z0-9]+Test)\b/', $text, $tests);
            foreach (array_unique($tests[1]) as $testClass) {
                $this->assertContains(
                    $testClass,
                    self::testClasses($root . '/tests'),
                    basename(dirname($file)) . " names {$testClass}, which does not exist"
                );
                $checked++;
            }
        }
        $this->assertGreaterThan(0, $checked);
    }

    /**
     * The short names of every test class under a directory.
     *
     * @return list<string>
     */
    private static function testClasses(string $directory): array
    {
        $names = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
        foreach ($iterator as $entry) {
            if ($entry instanceof \SplFileInfo && str_ends_with($entry->getFilename(), 'Test.php')) {
                $names[] = $entry->getBasename('.php');
            }
        }

        return $names;
    }

    /**
     * The plugin manifests advertise the skill's version; they have to say
     * what the skill's own frontmatter says.
     */
    public function testPluginManifestsCarryTheSkillVersion(): void
    {
        $skill = (string) file_get_contents(self::SKILL_DIR . '/SKILL.md');
        $this->assertSame(1, preg_match('/^\s+version: "([^"]+)"/m', $skill, $version));

        $root = __DIR__ . '/../../.claude-plugin';
        $plugin = json_decode((string) file_get_contents($root . '/plugin.json'), true, 512, JSON_THROW_ON_ERROR);
        $marketplace = json_decode((string) file_get_contents($root . '/marketplace.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame($version[1], $plugin['version'], 'plugin.json');
        $this->assertSame($version[1], $marketplace['plugins'][0]['version'], 'marketplace.json');
    }

    /**
     * The frontmatter names the SDK version the skill describes; that has to
     * be the version the changelog is at, so a release does not ship a skill
     * that claims an older one.
     */
    public function testFrontmatterNamesTheCurrentSdkVersion(): void
    {
        $skill = (string) file_get_contents(self::SKILL_DIR . '/SKILL.md');
        $this->assertSame(1, preg_match('/^\s+sdk_version: "([^"]+)"/m', $skill, $version));

        $changelog = (string) file_get_contents(__DIR__ . '/../../CHANGELOG.md');
        $this->assertSame(1, preg_match('/^## \[(\d+\.\d+\.\d+)\]/m', $changelog, $released));

        $this->assertSame($released[1], $version[1], 'SKILL.md sdk_version has to match the latest released version in CHANGELOG.md');
    }
}
