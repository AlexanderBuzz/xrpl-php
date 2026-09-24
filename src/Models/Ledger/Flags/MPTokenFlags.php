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
 * Flags of the MPToken ledger entry.
 *
 * Values are those of rippled 3.4.0 (LedgerFormats.h); combine them with |.
 * https://xrpl.org/mptoken.html
 */
final class MPTokenFlags
{
    use FlagSet;

    /** The balance, or the whole issuance, is locked. */
    public const lsfMPTLocked = 0x00000001;

    /** The issuer authorized the holder. */
    public const lsfMPTAuthorized = 0x00000002;

    /** The MPToken is held by an AMM. */
    public const lsfMPTAMM = 0x00000004;
}
