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
 * Flags of the LoanSet transaction.
 *
 * Values are those of rippled 3.4.0 (TxFlags.h); combine them with |.
 * Lending Protocol is not active on Mainnet at the time of writing.
 * https://xrpl.org/loanset.html
 */
final class LoanSetFlags
{
    use FlagSet;

    /** Allow, or make, a payment above the instalment due. */
    public const tfLoanOverpayment = 0x00010000;
}
