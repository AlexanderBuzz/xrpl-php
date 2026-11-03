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
 * Currently unused because with JSON-RPC there
 * is no persistent connection
 */
class DisconnectedException extends XrplException {}