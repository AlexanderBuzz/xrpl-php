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
 * Flags of the TrustSet transaction.
 *
 * Values are those of rippled 3.4.0 (TxFlags.h); combine them with |.
 * https://xrpl.org/trustset.html
 */
final class TrustSetFlags
{
    use FlagSet;

    /** Authorize the counterparty to hold tokens issued by this account. */
    public const tfSetfAuth = 0x00010000;

    /** Enable No Ripple on this trust line. */
    public const tfSetNoRipple = 0x00020000;

    /** Disable No Ripple on this trust line. */
    public const tfClearNoRipple = 0x00040000;

    /** Freeze the trust line. */
    public const tfSetFreeze = 0x00100000;

    /** Unfreeze the trust line. */
    public const tfClearFreeze = 0x00200000;

    /** Deep freeze the trust line: the counterparty can neither send nor receive the token. */
    public const tfSetDeepFreeze = 0x00400000;

    /** Clear the deep freeze. */
    public const tfClearDeepFreeze = 0x00800000;
}
