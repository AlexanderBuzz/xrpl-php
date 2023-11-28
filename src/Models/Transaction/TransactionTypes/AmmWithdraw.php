<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XRPL_PHP\Models\Transaction\TransactionTypes;

use XRPL_PHP\Core\RippleBinaryCodec\Types\Amount;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Issue;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/ammwithdraw.html
 */
class AmmWithdraw extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'Asset' => Issue::class,
        'Asset2' => Issue::class,
        'Amount' => Amount::class,
        'Amount2' => Amount::class,
        'EPrice' => Amount::class,
        'LPTokenOut' => Amount::class
    ];
}