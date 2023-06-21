<?php declare(strict_types=1);

namespace XRPL_PHP\Models\PathOrderbook;

use XRPL_PHP\Models\BaseRequest;

/**
 * public API Methods / Ledger Methods
 * https://xrpl.org/nft_sell_offers.html
 */
class NftSellOffersRequest extends BaseRequest
{
    protected string $command = "nft_sell_offers";

    public function __construct(
        protected string $nftId,
        protected ?string $ledgerHash = null,
        protected ?string $ledgerIndex = null,
        protected ?int $limit = null,
        protected mixed $marker = null
    ) {}
}