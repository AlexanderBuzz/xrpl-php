<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Transaction\TransactionTypes;

use XRPL_PHP\Core\RippleBinaryCodec\Types\Vector256;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/nftokencanceloffer.html
 */
class NFTokenCancelOffer extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'NFTokenOffers' => Vector256::class,
    ];
}