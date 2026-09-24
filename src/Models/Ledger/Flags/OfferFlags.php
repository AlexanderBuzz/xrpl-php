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
 * Flags of the Offer ledger entry.
 *
 * Values are those of rippled 3.4.0 (LedgerFormats.h); combine them with |.
 * https://xrpl.org/offer.html
 */
final class OfferFlags
{
    use FlagSet;

    /** The offer was placed with tfPassive. */
    public const lsfPassive = 0x00010000;

    /** The offer was placed with tfSell. */
    public const lsfSell = 0x00020000;

    /** The offer sits in the open and in a permissioned order book. */
    public const lsfHybrid = 0x00040000;
}
