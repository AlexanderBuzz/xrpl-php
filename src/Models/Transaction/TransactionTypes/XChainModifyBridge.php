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

use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Amount;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\XchainBridge;

/**
 * XChainModifyBridge transaction
 * https://xrpl.org/xchainmodifybridge.html
 */
class XChainModifyBridge extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'XChainBridge' => XchainBridge::class,
        'SignatureReward' => Amount::class,
        'MinAccountCreateAmount' => Amount::class,
    ];
}
