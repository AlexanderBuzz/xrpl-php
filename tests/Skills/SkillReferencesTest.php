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
