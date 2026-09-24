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
 * Flags of the SignerList ledger entry.
 *
 * Values are those of rippled 3.4.0 (LedgerFormats.h); combine them with |.
 * https://xrpl.org/signerlist.html
 */
final class SignerListFlags
{
    use FlagSet;

    /** The signer list counts as one item toward the owner reserve. */
    public const lsfOneOwnerCount = 0x00010000;
}
