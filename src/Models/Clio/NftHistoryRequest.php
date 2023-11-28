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
 * https://xrpl.org/nft_history.html
 */

class NftHistoryRequest extends BaseRequest
{
    protected string $command = "nft_history";

    public function __construct(
        protected string $nftId,
        protected ?int $ledgerIndexMin = null,
        protected ?int $ledgerIndexMax = null,
        protected ?string $ledgerHash = null,
        protected ?string $ledgerIndex = null,
        protected ?bool $binary = false,
        protected ?bool $forward = false,
        protected ?int $limit = null,
        protected mixed $marker = null
    ) {}
}