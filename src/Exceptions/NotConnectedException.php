<?php declare(strict_types=1);

namespace XRPL_PHP\Exceptions;

/**
 * Currently unused because with JSON-RPC there
 * is no persistent connection
 */
class NotConnectedException extends XrplException {}