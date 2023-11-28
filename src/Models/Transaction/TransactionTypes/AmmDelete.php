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

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/ammdelete.html
 */
class AmmDelete extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'Amount' => Amount::class,
        'Amount2' => Amount::class
    ];
}