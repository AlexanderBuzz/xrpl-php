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
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\XchainBridge;

/**
 * XChainAccountCreateCommit transaction
 * https://xrpl.org/xchainaccountcreatecommit.html
 */
class XChainAccountCreateCommit extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'XChainBridge' => XchainBridge::class,
        'SignatureReward' => Amount::class,
        'Destination' => AccountId::class,
        'Amount' => Amount::class,
    ];
}
