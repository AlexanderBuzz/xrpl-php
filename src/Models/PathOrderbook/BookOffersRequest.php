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