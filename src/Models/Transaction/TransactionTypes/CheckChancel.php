<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Transaction\TransactionTypes;

use XRPL_PHP\Core\RippleBinaryCodec\Types\Hash256;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/checkchancel.html
 */
class CheckChancel extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'CheckID' => Hash256::class
    ];
}