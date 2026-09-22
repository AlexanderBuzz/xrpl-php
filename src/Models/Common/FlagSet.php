<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Models\Common;

use ReflectionClass;

/**
 * What every flag constants class can do: list its flags, test one, and name
 * the flags set in a value read from the ledger.
 */
trait FlagSet
{
    /**
     * Every flag of this set, name => value.
     *
     * @return array<string, int>
     */
    public static function all(): array
    {
        /** @var array<string, int> */
        return (new ReflectionClass(static::class))->getConstants();
    }

    /**
     * Whether $flags has $flag set.
     *
     * @param int $flags
     * @param int $flag
     * @return bool
     */
    public static function has(int $flags, int $flag): bool
    {
        return ($flags & $flag) === $flag;
    }

    /**
     * The names of the flags set in $flags, in the order of their values.
     * Bits this set does not know are ignored.
     *
     * @param int $flags
     * @return list<string>
     */
    public static function parse(int $flags): array
    {
        $set = [];
        foreach (self::all() as $name => $value) {
            if (self::has($flags, $value)) {
                $set[] = $name;
            }
        }

        return $set;
    }
}
