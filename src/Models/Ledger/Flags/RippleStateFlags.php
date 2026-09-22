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
 * Flags of the RippleState ledger entry.
 *
 * Values are those of rippled 3.3.0 (LedgerFormats.h); combine them with |.
 * https://xrpl.org/ripplestate.html
 */
final class RippleStateFlags
{
    use FlagSet;

    /** The low account contributes to the owner reserve for this line. */
    public const lsfLowReserve = 0x00010000;

    /** The high account contributes to the owner reserve for this line. */
    public const lsfHighReserve = 0x00020000;

    /** The low account authorized the trust line. */
    public const lsfLowAuth = 0x00040000;

    /** The high account authorized the trust line. */
    public const lsfHighAuth = 0x00080000;

    /** The low account has No Ripple set on this line. */
    public const lsfLowNoRipple = 0x00100000;

    /** The high account has No Ripple set on this line. */
    public const lsfHighNoRipple = 0x00200000;

    /** The low account froze the trust line. */
    public const lsfLowFreeze = 0x00400000;

    /** The high account froze the trust line. */
    public const lsfHighFreeze = 0x00800000;

    /** The trust line belongs to an AMM. */
    public const lsfAMMNode = 0x01000000;

    /** The low account deep froze the trust line. */
    public const lsfLowDeepFreeze = 0x02000000;

    /** The high account deep froze the trust line. */
    public const lsfHighDeepFreeze = 0x04000000;
}
