<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Transaction\TransactionTypes;

use XRPL_PHP\Core\RippleBinaryCodec\Types\Amount;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Hash256;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/nftokenacceptoffer.html
 */
class NFTokenAcceptOffer extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'NFTokenSellOffer' => Hash256::class,
        'NFTokenBuyOffer' => Hash256::class,
        'NFTokenBrokerFee' => Amount::class
    ];
}