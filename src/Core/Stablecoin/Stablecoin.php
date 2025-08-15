<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Core\Stablecoin;

use Exception;

abstract class Stablecoin
{

    public static function getSettings(string $network): array
    {

    }

    public static function getAmount(string $network, string $value): array
    {

    }
}
