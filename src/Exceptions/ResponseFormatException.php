<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Exceptions;

/**
 * Error thrown when xrpl.js sees a response in the wrong format.
 */
class ResponseFormatException extends XrplException {}