<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Transaction\TransactionTypes;

use XRPL_PHP\Core\RippleBinaryCodec\Types\AccountId;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Amount;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Blob;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Hash256;
use XRPL_PHP\Core\RippleBinaryCodec\Types\PathSet;
use XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt32;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/paymentchannelclaim.html
 */
class PaymentChannelClaim extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'Channel' => Hash256::class,
        'Balance' => Amount::class,
        'Amount' => Amount::class,
        'Signature' => Blob::class,
        'PublicKey' => Blob::class,
    ];
}