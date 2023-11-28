<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XRPL_PHP\Models\PathOrderbook;

use XRPL_PHP\Models\BaseRequest;

/**
 * public API Methods / Ledger Methods
 * https://xrpl.org/deposit_authorized.html
 */
class DepositAuthorizedRequest extends BaseRequest
{
    protected string $command = "deposit_authorized";

    public function __construct(
        protected string $sourceAccount,
        protected string $destinationAccount,
        protected ?string $ledgerHash = null,
        protected ?string $ledgerIndex = null
    ) {}
}