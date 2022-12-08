<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Transaction\TransactionTypes;

use XRPL_PHP\Core\RippleBinaryCodec\Types\AccountId;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Hash256;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/nftokenburn.html
 */
class NFTokenBurn extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'NFTokenID' => Hash256::class,
        'Owner' => AccountId::class
    ];
}