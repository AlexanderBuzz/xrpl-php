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
 * Flags of the AccountRoot ledger entry.
 *
 * Values are those of rippled 3.4.0 (LedgerFormats.h); combine them with |.
 * https://xrpl.org/accountroot.html
 */
final class AccountRootFlags
{
    use FlagSet;

    /** The account has used its free SetRegularKey transaction. */
    public const lsfPasswordSpent = 0x00010000;

    /** Set by AccountSet with asfRequireDestTag. */
    public const lsfRequireDestTag = 0x00020000;

    /** Set by AccountSet with asfRequireAuth. */
    public const lsfRequireAuth = 0x00040000;

    /** Set by AccountSet with asfDisallowXRP. */
    public const lsfDisallowXRP = 0x00080000;

    /** Set by AccountSet with asfDisableMaster. */
    public const lsfDisableMaster = 0x00100000;

    /** Set by AccountSet with asfNoFreeze. */
    public const lsfNoFreeze = 0x00200000;

    /** Set by AccountSet with asfGlobalFreeze. */
    public const lsfGlobalFreeze = 0x00400000;

    /** Set by AccountSet with asfDefaultRipple. */
    public const lsfDefaultRipple = 0x00800000;

    /** Set by AccountSet with asfDepositAuth. */
    public const lsfDepositAuth = 0x01000000;

    /** Set by AccountSet with asfDisallowIncomingNFTokenOffer. */
    public const lsfDisallowIncomingNFTokenOffer = 0x04000000;

    /** Set by AccountSet with asfDisallowIncomingCheck. */
    public const lsfDisallowIncomingCheck = 0x08000000;

    /** Set by AccountSet with asfDisallowIncomingPayChan. */
    public const lsfDisallowIncomingPayChan = 0x10000000;

    /** Set by AccountSet with asfDisallowIncomingTrustline. */
    public const lsfDisallowIncomingTrustline = 0x20000000;

    /** Set by AccountSet with asfAllowTrustLineLocking. */
    public const lsfAllowTrustLineLocking = 0x40000000;

    /** Set by AccountSet with asfAllowTrustLineClawback. */
    public const lsfAllowTrustLineClawback = 0x80000000;
}
