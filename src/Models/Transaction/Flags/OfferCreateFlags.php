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
 * Flags of the OfferCreate transaction.
 *
 * Values are those of rippled 3.3.0 (TxFlags.h); combine them with |.
 * https://xrpl.org/offercreate.html
 */
final class OfferCreateFlags
{
    use FlagSet;

    /** Do not consume offers that exactly match this one; only cross offers of better quality. */
    public const tfPassive = 0x00010000;

    /** Fill what can be filled immediately and cancel the rest; the offer is never placed in the ledger. */
    public const tfImmediateOrCancel = 0x00020000;

    /** Fill the whole offer immediately or cancel it. */
    public const tfFillOrKill = 0x00040000;

    /** Exchange the entire TakerGets amount, even if that yields more than TakerPays. */
    public const tfSell = 0x00080000;

    /** Place the offer in the open order book as well as in the permissioned book of DomainID (PermissionedDEX). */
    public const tfHybrid = 0x00100000;
}
