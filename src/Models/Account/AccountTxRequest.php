<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Account;

use XRPL_PHP\Models\BaseRequest;

/**
 * public API Methods / Ledger Methods
 * https://xrpl.org/account_tx.html
 */
class AccountTxRequest extends BaseRequest
{
    protected string $command = "account_tx";

    public function __construct(
        protected string $account,
        protected ?int $ledgerIndexMin = null,
        protected ?int $ledgerIndexMax = null,
        protected ?string $ledgerHash = null,
        protected ?string $ledgerIndex = null,
        protected ?bool $binary = null,
        protected ?bool $forward = null,
        protected ?int $limit = null,
        protected mixed $marker = null,
        protected ?bool $strict = null
    ) {}
}