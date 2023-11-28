<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XRPL_PHP\Models\Transaction;

use XRPL_PHP\Models\BaseRequest;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/transaction_entry.html
 */
class TransactionEntryRequest extends BaseRequest
{
    protected string $command = "transaction_entry";

    public function __construct(
        protected string $txHash,
        protected ?string $ledgerHash = null,
        protected ?string $ledgerIndex = null
    ) {}
}