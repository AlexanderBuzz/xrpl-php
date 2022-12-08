<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Transaction\TransactionTypes;

use XRPL_PHP\Core\RippleBinaryCodec\Types\AccountId;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Amount;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Blob;
use XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt32;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/paymentchannelcreate.html
 */
class PaymentChannelCreate extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'Amount' => Amount::class,
        'Destination' => AccountId::class,
        'SettleDelay' => UnsignedInt32::class,
        'PublicKey' => Blob::class,
        'ChancelAfter' => UnsignedInt32::class,
        'DestinationTag' => UnsignedInt32::class,
    ];
}