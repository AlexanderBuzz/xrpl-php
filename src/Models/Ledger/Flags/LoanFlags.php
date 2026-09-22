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
 * Flags of the Loan ledger entry.
 *
 * Values are those of rippled 3.3.0 (LedgerFormats.h); combine them with |.
 * Lending Protocol is not active on Mainnet at the time of writing.
 * https://xrpl.org/loan.html
 */
final class LoanFlags
{
    use FlagSet;

    /** The loan is in default. */
    public const lsfLoanDefault = 0x00010000;

    /** The loan is impaired. */
    public const lsfLoanImpaired = 0x00020000;

    /** The loan allows overpayments. */
    public const lsfLoanOverpayment = 0x00040000;
}
