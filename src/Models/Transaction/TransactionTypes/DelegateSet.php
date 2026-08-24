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
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\StArray;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/delegateset.html
 */
class DelegateSet extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'Authorize' => AccountId::class,
        'Permissions' => StArray::class,
    ];
}
