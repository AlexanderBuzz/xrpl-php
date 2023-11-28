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
 * https://xrpl.org/gateway_balances.html
 */
class GatewayBalancesRequest extends BaseRequest
{
    protected string $command = "gateway_balances";

    public function __construct(
        protected string $account,
        protected ?bool $strict = null,
        protected mixed $hotwallet = null, //string or array, in PHP mixed can be null
        protected ?string $ledgerHash = null,
        protected ?string $ledgerIndex = null
    ) {}


}