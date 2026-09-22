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
 * Flags of the SponsorshipTransfer transaction.
 *
 * Values are those of rippled 3.3.0 (TxFlags.h); combine them with |.
 * Sponsorship is not active on Mainnet at the time of writing.
 * https://xrpl.org/sponsorshiptransfer.html
 */
final class SponsorshipTransferFlags
{
    use FlagSet;

    public const tfSponsorshipEnd = 0x00010000;

    public const tfSponsorshipCreate = 0x00020000;

    public const tfSponsorshipReassign = 0x00040000;
}
