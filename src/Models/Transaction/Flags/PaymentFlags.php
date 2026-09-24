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
 * Flags of the Payment transaction.
 *
 * Values are those of rippled 3.4.0 (TxFlags.h); combine them with |.
 * https://xrpl.org/payment.html
 */
final class PaymentFlags
{
    use FlagSet;

    /** Do not use the default path; only use the paths in the Paths field. */
    public const tfNoRippleDirect = 0x00010000;

    /** Deliver less than Amount if the full amount cannot be delivered, down to DeliverMin. */
    public const tfPartialPayment = 0x00020000;

    /** Only use paths whose overall quality is at least Amount to SendMax. */
    public const tfLimitQuality = 0x00040000;

    /** The sender sponsors the reserve of the account this payment creates. Sponsorship is not active on Mainnet. */
    public const tfSponsorCreatedAccount = 0x00080000;
}
