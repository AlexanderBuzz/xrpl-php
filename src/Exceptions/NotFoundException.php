<?php declare(strict_types=1);

namespace XRPL_PHP\Exceptions;

/**
 * Error thrown when xrpl.js cannot retrieve a transaction, ledger, account, etc.
 * From rippled.
 */
class NotFoundException extends XrplException {
    /**
     * Constructor
     *
     * @param string $message
     */
    public function __construct(string $message = 'Not found') {

        parent::__construct($message);
    }
}