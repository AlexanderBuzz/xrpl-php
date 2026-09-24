<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Models\Transaction\Flags;

use Hardcastle\XRPL_PHP\Models\Common\FlagSet;

/**
 * Flags of the NFTokenCreateOffer transaction.
 *
 * Values are those of rippled 3.4.0 (TxFlags.h); combine them with |.
 * https://xrpl.org/nftokencreateoffer.html
 */
final class NFTokenCreateOfferFlags
{
    use FlagSet;

    /** This is a sell offer; without it, a buy offer. */
    public const tfSellNFToken = 0x00000001;
}
