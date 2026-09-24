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
 * Flags of the SponsorshipSet transaction.
 *
 * Values are those of rippled 3.4.0 (TxFlags.h); combine them with |.
 * Sponsorship is not active on Mainnet at the time of writing.
 * https://xrpl.org/sponsorshipset.html
 */
final class SponsorshipSetFlags
{
    use FlagSet;

    public const tfSponsorshipSetRequireSignForFee = 0x00010000;

    public const tfSponsorshipClearRequireSignForFee = 0x00020000;

    public const tfSponsorshipSetRequireSignForReserve = 0x00040000;

    public const tfSponsorshipClearRequireSignForReserve = 0x00080000;

    /** Delete the Sponsorship entry. */
    public const tfDeleteObject = 0x00100000;
}
