<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Transaction\TransactionTypes;

use XRPL_PHP\Core\RippleBinaryCodec\Types\AccountId;
use XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt32;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/accountdelete.html
 */
class AccountDelete extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'Destination' => AccountId::class,
        'DestinationTag' => UnsignedInt32::class
    ];
}