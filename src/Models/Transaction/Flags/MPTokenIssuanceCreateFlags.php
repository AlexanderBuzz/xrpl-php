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
 * Flags of the MPTokenIssuanceCreate transaction.
 *
 * Values are those of rippled 3.3.0 (TxFlags.h); combine them with |.
 * https://xrpl.org/mptokenissuancecreate.html
 */
final class MPTokenIssuanceCreateFlags
{
    use FlagSet;

    /** The issuer can lock balances. */
    public const tfMPTCanLock = 0x00000002;

    /** Holders need the issuer's authorization. */
    public const tfMPTRequireAuth = 0x00000004;

    /** Holders can place balances in escrow. */
    public const tfMPTCanEscrow = 0x00000008;

    /** Holders can trade balances on the DEX. */
    public const tfMPTCanTrade = 0x00000010;

    /** Holders can transfer balances to accounts other than the issuer. */
    public const tfMPTCanTransfer = 0x00000020;

    /** The issuer can claw back balances. */
    public const tfMPTCanClawback = 0x00000040;

    /** Holders can convert balances into confidential ones. ConfidentialMPT is not active on Mainnet. */
    public const tfMPTCanHoldConfidentialBalance = 0x00000080;
}
