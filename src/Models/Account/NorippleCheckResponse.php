<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XRPL_PHP\Models\Account;

use XRPL_PHP\Models\BaseResponse;

/**
 * public API Methods / Ledger Methods
 * https://xrpl.org/noripple_check.html
 */
class NorippleCheckResponse extends BaseResponse
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