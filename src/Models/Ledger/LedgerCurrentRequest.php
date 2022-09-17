<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Ledger;

use XRPL_PHP\Models\BaseRequest;
use XRPL_PHP\Models\Transactions\Hash256;

/**
 * public API Methods / Ledger Methods
 * https://xrpl.org/ledger_current.html
 */
class LedgerCurrentRequest extends BaseRequest
{
    protected string $command = "ledger_current";

    public function __construct(
        protected string|int $id
    ) {}
}