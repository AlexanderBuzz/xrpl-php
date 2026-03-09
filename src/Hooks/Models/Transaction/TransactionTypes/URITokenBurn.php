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
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Hash256;

/**
 * URITokenBurn transaction (Xahau/Hooks).
 */
class URITokenBurn extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'URITokenID' => Hash256::class,
    ];
}
