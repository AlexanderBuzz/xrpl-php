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
 * Flags of the Batch transaction.
 *
 * Values are those of rippled 3.4.0 (TxFlags.h); combine them with |.
 * Batch is not active on Mainnet at the time of writing.
 * https://xrpl.org/batch.html
 */
final class BatchFlags
{
    use FlagSet;

    /** Apply all inner transactions, or none of them. */
    public const tfAllOrNothing = 0x00010000;

    /** Apply the first inner transaction that succeeds and stop. */
    public const tfOnlyOne = 0x00020000;

    /** Apply the inner transactions in order until the first one fails. */
    public const tfUntilFailure = 0x00040000;

    /** Apply every inner transaction, whatever the others do. */
    public const tfIndependent = 0x00080000;
}
