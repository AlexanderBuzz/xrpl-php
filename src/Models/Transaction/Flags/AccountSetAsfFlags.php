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
 * Values of the SetFlag and ClearFlag fields of AccountSet.
 *
 * Values are those of rippled 3.4.0 (TxFlags.h); one per transaction, they are not bit flags.
 * https://xrpl.org/accountset.html
 */
final class AccountSetAsfFlags
{
    use FlagSet;

    /** Require a DestinationTag on incoming payments. */
    public const asfRequireDest = 0x00000001;

    /** Require this account's authorization for others to hold its issued tokens. */
    public const asfRequireAuth = 0x00000002;

    /** XRP should not be sent to this account. Advisory, not enforced by the ledger. */
    public const asfDisallowXRP = 0x00000003;

    /** Disallow the master key pair. Needs a regular key or signer list first. */
    public const asfDisableMaster = 0x00000004;

    /** Track the ID of this account's most recent transaction in AccountTxnID. */
    public const asfAccountTxnID = 0x00000005;

    /** Permanently give up the ability to freeze trust lines or to global freeze. */
    public const asfNoFreeze = 0x00000006;

    /** Freeze all tokens issued by this account. */
    public const asfGlobalFreeze = 0x00000007;

    /** Enable rippling on this account's trust lines by default. */
    public const asfDefaultRipple = 0x00000008;

    /** Require deposit authorization for incoming payments. */
    public const asfDepositAuth = 0x00000009;

    /** Let the account in NFTokenMinter mint NFTs on behalf of this one. */
    public const asfAuthorizedNFTokenMinter = 0x0000000A;

    /** Block incoming NFToken offers. */
    public const asfDisallowIncomingNFTokenOffer = 0x0000000C;

    /** Block incoming Checks. */
    public const asfDisallowIncomingCheck = 0x0000000D;

    /** Block incoming payment channels. */
    public const asfDisallowIncomingPayChan = 0x0000000E;

    /** Block incoming trust lines. */
    public const asfDisallowIncomingTrustline = 0x0000000F;

    /** Allow clawback of tokens issued by this account. Cannot be cleared once set. */
    public const asfAllowTrustLineClawback = 0x00000010;

    /** Allow tokens issued by this account to be held in escrow (TokenEscrow). */
    public const asfAllowTrustLineLocking = 0x00000011;
}
