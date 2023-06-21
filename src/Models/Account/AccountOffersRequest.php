<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Account;

use XRPL_PHP\Models\BaseRequest;

/**
 * public API Methods / Ledger Methods
 * https://xrpl.org/account_offers.html
 */
class AccountOffersRequest extends BaseRequest
{
    protected string $command = "account_offers";

    public function __construct(
        protected string $account,
        //protected ?string $ledger = null, //deprecated
        protected ?string $ledgerHash = null,
        protected ?string $ledgerIndex = null,
        protected ?int $limit = null,
        protected mixed $marker = null,
        protected ?bool $strict = null
    ) {}
}