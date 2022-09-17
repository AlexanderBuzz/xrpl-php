<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Account;

use XRPL_PHP\Models\BaseRequest;
use XRPL_PHP\Models\Transactions\Hash256;

/**
 * public API Methods / Ledger Methods
 * https://xrpl.org/account_lines.html
 */
class AccountLinesRequest extends BaseRequest
{
    protected string $command = "account_lines";

    public function __construct(
        protected string $account,
        protected ?string $ledgerHash = null,
        protected ?string $ledgerIndex = null,
        protected ?string $peer = null,
        protected ?int $limit = null,
        //TODO: Marker https://xrpl.org/markers-and-pagination.html
    ) {}
}