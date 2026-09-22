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
 * Flags of the EnableAmendment transaction.
 *
 * Values are those of rippled 3.3.0 (TxFlags.h); combine them with |.
 * https://xrpl.org/enableamendment.html
 */
final class EnableAmendmentFlags
{
    use FlagSet;

    /** The amendment reached majority support. */
    public const tfGotMajority = 0x00010000;

    /** The amendment lost majority support. */
    public const tfLostMajority = 0x00020000;
}
