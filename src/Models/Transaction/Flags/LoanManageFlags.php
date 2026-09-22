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
 * Flags of the LoanManage transaction.
 *
 * Values are those of rippled 3.3.0 (TxFlags.h); combine them with |.
 * Lending Protocol is not active on Mainnet at the time of writing.
 * https://xrpl.org/loanmanage.html
 */
final class LoanManageFlags
{
    use FlagSet;

    /** Mark the loan as defaulted. */
    public const tfLoanDefault = 0x00010000;

    /** Mark the loan as impaired. */
    public const tfLoanImpair = 0x00020000;

    /** Clear the impairment. */
    public const tfLoanUnimpair = 0x00040000;
}
