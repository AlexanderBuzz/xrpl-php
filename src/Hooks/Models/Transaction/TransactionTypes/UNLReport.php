<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Hooks\Models\Transaction\TransactionTypes;

use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\BaseTransaction;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\StArray;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt64;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt32;

/**
 * UNLReport transaction model (Xahau).
 */
class UNLReport extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'ActiveAccounts' => StArray::class,
        'RewardAccumulator' => UnsignedInt64::class,
        'RewardLgrFirst' => UnsignedInt32::class,
        'RewardLgrLast' => UnsignedInt32::class,
    ];
}
