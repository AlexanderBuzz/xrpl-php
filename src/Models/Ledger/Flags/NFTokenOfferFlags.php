<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Models\Ledger\Flags;

use Hardcastle\XRPL_PHP\Models\Common\FlagSet;

/**
 * Flags of the NFTokenOffer ledger entry.
 *
 * Values are those of rippled 3.4.0 (LedgerFormats.h); combine them with |.
 * https://xrpl.org/nftokenoffer.html
 */
final class NFTokenOfferFlags
{
    use FlagSet;

    /** The offer is a sell offer. */
    public const lsfSellNFToken = 0x00000001;
}
