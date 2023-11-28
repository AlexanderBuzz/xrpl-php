<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XRPL_PHP\Models\Utility;

use XRPL_PHP\Models\BaseRequest;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/random.html
 */
final class RandomRequest extends BaseRequest
{
    protected string $command = "random";
}