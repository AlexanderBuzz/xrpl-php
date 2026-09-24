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
 * Flags of the AMMWithdraw transaction.
 *
 * Values are those of rippled 3.4.0 (TxFlags.h); combine them with |.
 * https://xrpl.org/ammwithdraw.html
 */
final class AMMWithdrawFlags
{
    use FlagSet;

    /** Deposit or withdraw both assets for exactly the LP token amount. */
    public const tfLPToken = 0x00010000;

    /** Withdraw everything the LP tokens are worth. */
    public const tfWithdrawAll = 0x00020000;

    /** Withdraw everything as a single asset. */
    public const tfOneAssetWithdrawAll = 0x00040000;

    /** Deposit or withdraw exactly Amount of one asset. */
    public const tfSingleAsset = 0x00080000;

    /** Deposit or withdraw both assets, up to Amount and Amount2, at the pool's ratio. */
    public const tfTwoAsset = 0x00100000;

    /** Deposit or withdraw one asset for exactly the LP token amount. */
    public const tfOneAssetLPToken = 0x00200000;

    /** Deposit or withdraw one asset at an effective price bounded by EPrice. */
    public const tfLimitLPToken = 0x00400000;
}
