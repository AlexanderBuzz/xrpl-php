<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Models\Account;

use Hardcastle\XRPL_PHP\Models\BaseRequest;

/**
 * public API Methods / Ledger Methods
 * https://xrpl.org/account_objects.html
 */
class AccountObjectsRequest extends BaseRequest
{
    protected string $command = "account_objects";

    public function __construct(
        protected string $account,
        protected ?string $type = null,
        protected ?string $ledgerHash = null,
        protected ?string $ledgerIndex = null,
        protected ?int $limit = null,
        protected ?bool $deletionBlockersOnly = null,
        protected mixed $marker = null
    ) {}
}