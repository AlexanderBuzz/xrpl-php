<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Ledger;

use XRPL_PHP\Models\BaseRequest;
use XRPL_PHP\Models\Transactions\Hash256;

/**
 * public API Methods / Ledger Methods
 * https://xrpl.org/ledger.html
 */
class LedgerRequest extends BaseRequest //JsonSerializable https://www.php.net/manual/en/class.jsonserializable.php
{
    protected string $command = "ledger";

    public function __construct(
        protected ?string $ledgerHash = null,
        protected ?string $ledgerIndex = null,
        protected ?bool $full = false,
        protected ?bool $accounts = false,
        protected ?bool $transactions = false,
        protected ?bool $expand = false,
        protected ?bool $ownerFunds = false,
        protected ?bool $binary = false,
        protected ?bool $queue = false
    ) {}
}