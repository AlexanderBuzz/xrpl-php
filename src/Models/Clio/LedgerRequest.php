<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XRPL_PHP\Models\Clio;

use XRPL_PHP\Models\BaseRequest;

/**
 * public API Methods / Clio Methods
 * https://xrpl.org/ledger-clio.html
 */

class LedgerRequest extends BaseRequest
{
    protected string $command = "ledger";

    public function __construct(
        protected ?string $ledgerHash = null,
        protected ?string $ledgerIndex = null,
        protected ?bool $transactions = false,
        protected ?bool $expand = false,
        protected ?bool $ownerFunds = false,
        protected ?bool $binary = false,
        protected ?bool $queue = false,
        protected ?bool $diff = false
    ) {}
}