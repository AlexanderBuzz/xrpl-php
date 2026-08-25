<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Exceptions;
/**
 * Raised when a transaction or value fails a check before it ever reaches the
 * network.
 */
class ValidationException extends XrplException {}