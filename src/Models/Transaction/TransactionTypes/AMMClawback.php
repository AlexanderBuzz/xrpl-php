<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes;

use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\AccountId;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Amount;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Issue;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/ammclawback.html
 */
class AMMClawback extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'Holder' => AccountId::class,
        'Asset' => Issue::class,
        'Asset2' => Issue::class,
        'Amount' => Amount::class,
    ];
}
