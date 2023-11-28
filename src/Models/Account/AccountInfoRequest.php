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

use XRPL_PHP\Models\BaseRequest;

/**
 * public API Methods / Ledger Methods
 * https://xrpl.org/account_info.html
 */
class AccountInfoRequest extends BaseRequest
{
    protected string $command = "account_info";

    public function __construct(
        protected string $account,
        protected ?string $ledgerHash = null,
        protected ?string $ledgerIndex = null,
        protected ?bool $queue = null,
        protected ?bool $signer_lists = null,
        protected ?bool $strict = null
    ) {}
}