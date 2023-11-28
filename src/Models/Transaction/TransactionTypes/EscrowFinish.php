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
use XRPL_PHP\Core\RippleBinaryCodec\Types\Blob;
use XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt32;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/escrowfinish.html
 */
class EscrowFinish extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'Owner' => AccountId::class,
        'OfferSequence' => UnsignedInt32::class,
        'Condition' => Blob::class,
        'Fulfillment' => Blob::class,
    ];
}