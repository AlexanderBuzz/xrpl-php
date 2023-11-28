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

use XRPL_PHP\Core\RippleBinaryCodec\Types\AccountId;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Amount;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Hash256;
use XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt32;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/checkcreate.html
 */
class CheckCreate extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'Destination' => AccountId::class,
        'SendMax' => Amount::class,
        'DestinationTag' => UnsignedInt32::class,
        'Expiration' => UnsignedInt32::class,
        'InvoiceID' =>Hash256::class
    ];
}