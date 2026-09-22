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
 * Flags of the DirectoryNode ledger entry.
 *
 * Values are those of rippled 3.3.0 (LedgerFormats.h); combine them with |.
 * https://xrpl.org/directorynode.html
 */
final class DirectoryNodeFlags
{
    use FlagSet;

    /** The directory holds buy offers for an NFToken. */
    public const lsfNFTokenBuyOffers = 0x00000001;

    /** The directory holds sell offers for an NFToken. */
    public const lsfNFTokenSellOffers = 0x00000002;
}
