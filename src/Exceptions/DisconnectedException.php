<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Exceptions;

/**
 * Currently unused because with JSON-RPC there
 * is no persistent connection
 */
class DisconnectedException extends XrplException {}