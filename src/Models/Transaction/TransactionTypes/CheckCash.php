<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Transaction\TransactionTypes;

use XRPL_PHP\Core\RippleBinaryCodec\Types\Amount;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Hash256;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/checkchash.html
 */
class CheckCash extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'CheckID' => Hash256::class,
        'Amount' => Amount::class,
        'DeliverMin' => Hash256::class,
    ];
}