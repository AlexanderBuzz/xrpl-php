<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Account;

use XRPL_PHP\Models\BaseRequest;

/**
 * public API Methods / Ledger Methods
 * https://xrpl.org/account_channels.html
 */
class AccountChannelsRequest extends BaseRequest
{
    protected string $command = "account_channels";

    public function __construct(
        protected string $account,
        protected ?string $destinationAccount = null,
        protected ?string $ledgerHash = null,
        protected ?string $ledgerIndex = null,
        protected ?int $limit = null,
        protected mixed $marker = null
    ) {}
}