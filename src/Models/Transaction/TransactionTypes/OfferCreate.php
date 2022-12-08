<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Transaction\TransactionTypes;

use XRPL_PHP\Core\RippleBinaryCodec\Types\Amount;
use XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt32;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/offercreate.html
 */
class OfferCreate extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'Expiration' => UnsignedInt32::class,
        'OfferSequence' => UnsignedInt32::class,
        'TakerGets' => Amount::class,
        'TakerPays' => Amount::class,
    ];
}