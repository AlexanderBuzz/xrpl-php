<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Account;

use XRPL_PHP\Models\BaseRequest;
use XRPL_PHP\Models\Transactions\Hash256;

/**
 * public API Methods / Ledger Methods
 * https://xrpl.org/gateway_balances.html
 */
class NorippleCheckRequest extends BaseRequest
{
    protected string $command = "noripple_check";

    public function __construct(
        protected string $account,
        protected string $role,
        protected ?bool $transactions = null,
        protected ?int $limit = null,
        protected ?string $ledgerHash = null,
        protected ?string $ledgerIndex = null
    ) {}


}