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
 * Flags of the XChainModifyBridge transaction.
 *
 * Values are those of rippled 3.3.0 (TxFlags.h); combine them with |.
 * https://xrpl.org/xchainmodifybridge.html
 */
final class XChainModifyBridgeFlags
{
    use FlagSet;

    /** Clear the bridge's MinAccountCreateAmount. */
    public const tfClearAccountCreateAmount = 0x00010000;
}
