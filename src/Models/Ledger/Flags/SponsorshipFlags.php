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
 * Flags of the Sponsorship ledger entry.
 *
 * Values are those of rippled 3.4.0 (LedgerFormats.h); combine them with |.
 * Sponsorship is not active on Mainnet at the time of writing.
 * https://xrpl.org/sponsorship.html
 */
final class SponsorshipFlags
{
    use FlagSet;

    public const lsfSponsorshipRequireSignForFee = 0x00010000;

    public const lsfSponsorshipRequireSignForReserve = 0x00020000;
}
