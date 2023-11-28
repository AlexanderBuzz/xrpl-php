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
 * https://xrpl.org/ripple_path_find.html
 */
class RipplePathFindRequest extends BaseRequest
{
    protected string $command = "ripple_path_find";

    public function __construct(
        protected string $sourceAccount,
        protected string $destinationAccount,
        protected string|array $destinationAmount,
        protected string|array|null $sendMax = null,
        protected ?array $sourceCurrencies = null,
        protected ?string $ledgerHash = null,
        protected ?string $ledgerIndex = null
    ) {}
}