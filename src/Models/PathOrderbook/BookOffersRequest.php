<?php declare(strict_types=1);

namespace XRPL_PHP\Models\PathOrderbook;

use XRPL_PHP\Models\BaseRequest;

/**
 * public API Methods / Ledger Methods
 * https://xrpl.org/book_offers.html
 */
class BookOffersRequest extends BaseRequest
{
    protected string $command = "book_offers";

    public function __construct(
        protected array $takerGets,
        protected array $takerPays,
        protected ?string $ledgerHash = null,
        protected ?string $ledgerIndex = null,
        protected ?int $number = null,
        protected ?string $taker = null
    ) {}
}