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
 * Flags of the MPTokenIssuanceSet transaction.
 *
 * Values are those of rippled 3.4.0 (TxFlags.h); combine them with |.
 * https://xrpl.org/mptokenissuanceset.html
 */
final class MPTokenIssuanceSetFlags
{
    use FlagSet;

    /** Lock the issuance, or with Holder that holder's balance. */
    public const tfMPTLock = 0x00000001;

    /** Unlock the issuance, or with Holder that holder's balance. */
    public const tfMPTUnlock = 0x00000002;

    /** Set tfMPTCanLock after creation (DynamicMPT). */
    public const tfMPTSetCanLock = 0x00000004;

    /** Set tfMPTRequireAuth after creation (DynamicMPT). */
    public const tfMPTSetRequireAuth = 0x00000008;

    /** Set tfMPTCanEscrow after creation (DynamicMPT). */
    public const tfMPTSetCanEscrow = 0x00000010;

    /** Set tfMPTCanTrade after creation (DynamicMPT). */
    public const tfMPTSetCanTrade = 0x00000020;

    /** Set tfMPTCanTransfer after creation (DynamicMPT). */
    public const tfMPTSetCanTransfer = 0x00000040;

    /** Set tfMPTCanClawback after creation (DynamicMPT). */
    public const tfMPTSetCanClawback = 0x00000080;

    /** Set tfMPTCanHoldConfidentialBalance after creation (DynamicMPT, ConfidentialMPT). */
    public const tfMPTSetCanHoldConfidentialBalance = 0x00000100;
}
