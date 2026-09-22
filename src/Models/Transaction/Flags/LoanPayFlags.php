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
 * Flags of the LoanPay transaction.
 *
 * Values are those of rippled 3.3.0 (TxFlags.h); combine them with |.
 * Lending Protocol is not active on Mainnet at the time of writing.
 * https://xrpl.org/loanpay.html
 */
final class LoanPayFlags
{
    use FlagSet;

    /** Allow, or make, a payment above the instalment due. */
    public const tfLoanOverpayment = 0x00010000;

    /** Pay the loan off in full. */
    public const tfLoanFullPayment = 0x00020000;

    /** Pay an overdue instalment. */
    public const tfLoanLatePayment = 0x00040000;
}
