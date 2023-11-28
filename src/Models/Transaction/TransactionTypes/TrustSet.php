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
use XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt32;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/trustset.html
 */
class TrustSet extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'LimitAmount' => Amount::class,
        'QualityIn' => UnsignedInt32::class,
        'QualityOut' => UnsignedInt32::class,
    ];
}