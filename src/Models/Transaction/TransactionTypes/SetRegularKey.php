<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Transaction\TransactionTypes;

use XRPL_PHP\Core\RippleBinaryCodec\Types\AccountId;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/setregularkey.html
 */
class SetRegularKey extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'RegularKey' => AccountId::class,
    ];
}