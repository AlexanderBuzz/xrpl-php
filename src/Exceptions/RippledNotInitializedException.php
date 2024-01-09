<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Exceptions;

/**
 * Error thrown when rippled is not initialized.
 */
class RippledNotInitializedException extends XrplException {}