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
 * https://xrpl.org/amm_info.html
 */
class AmmInfoRequest extends BaseRequest
{
    protected string $command = "amm_info";

    public function __construct(
        protected ?string $account,
        protected ?string $amm_account,
        protected string|array|null $asset = null,
        protected string|array|null $asset2 = null
    ) {
        if (is_null($this->amm_account)) {
            if(is_null($this->asset) && is_null($this->asset2)) {
                throw new \InvalidArgumentException("amm_account or asset and asset2 must be set");
            }
        }
    }
}