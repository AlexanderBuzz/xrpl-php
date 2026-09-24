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
 * Flags of the MPTokenIssuance ledger entry.
 *
 * Values are those of rippled 3.4.0 (LedgerFormats.h); combine them with |.
 * https://xrpl.org/mptokenissuance.html
 */
final class MPTokenIssuanceFlags
{
    use FlagSet;

    /** The balance, or the whole issuance, is locked. */
    public const lsfMPTLocked = 0x00000001;

    /** The issuer can lock balances. */
    public const lsfMPTCanLock = 0x00000002;

    /** Holders need the issuer's authorization. */
    public const lsfMPTRequireAuth = 0x00000004;

    /** Holders can place balances in escrow. */
    public const lsfMPTCanEscrow = 0x00000008;

    /** Holders can trade balances on the DEX. */
    public const lsfMPTCanTrade = 0x00000010;

    /** Holders can transfer balances to accounts other than the issuer. */
    public const lsfMPTCanTransfer = 0x00000020;

    /** The issuer can claw back balances. */
    public const lsfMPTCanClawback = 0x00000040;

    /** Holders can convert balances into confidential ones. */
    public const lsfMPTCanHoldConfidentialBalance = 0x00000080;
}
