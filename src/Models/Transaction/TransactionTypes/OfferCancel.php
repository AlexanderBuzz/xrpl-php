<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Transaction\TransactionTypes;

use XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt32;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/offercancel.html
 */
class OfferCancel extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'OfferSequence' => UnsignedInt32::class,
    ];
}