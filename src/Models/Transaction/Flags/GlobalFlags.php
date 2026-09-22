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
 * Flags every transaction type accepts.
 *
 * Values are those of rippled 3.3.0 (TxFlags.h); combine them with |.
 * https://xrpl.org/docs/references/protocol/transactions/common-fields#flags-field
 */
final class GlobalFlags
{
    use FlagSet;

    /** Marks an inner transaction of a Batch. Such a transaction carries no signature of its own. Batch is not active on Mainnet. */
    public const tfInnerBatchTxn = 0x40000000;

    /** Require a fully canonical signature. A no-op since the RequireFullyCanonicalSig amendment, kept for compatibility. */
    public const tfFullyCanonicalSig = 0x80000000;
}
