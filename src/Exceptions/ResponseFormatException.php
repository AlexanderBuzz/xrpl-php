<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Exceptions;

/**
 * Error thrown when xrpl.js sees a response in the wrong format.
 */
class ResponseFormatException extends XrplException {}