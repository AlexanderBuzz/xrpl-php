<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Clio;

use XRPL_PHP\Models\BaseRequest;

/**
 * public API Methods / Clio Methods
 * https://xrpl.org/nft_info.html
 */

class NftInfoRequest extends BaseRequest
{
    protected string $command = "nft_info";

    public function __construct(
        protected string $nftId,
        protected ?string $ledgerHash = null,
        protected ?string $ledgerIndex = null
    ) {}
}