<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Transaction\TransactionTypes;

use XRPL_PHP\Core\RippleBinaryCodec\Types\AccountId;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Amount;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Blob;
use XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt32;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/escrowcreate.html
 */
class EscrowCreate extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'Amount' => Amount::class,
        'Destination' => AccountId::class,
        'CancelAfter' => UnsignedInt32::class,
        'FinishAfter' => UnsignedInt32::class,
        'Condition' => Blob::class,
        'DestinationTag' => UnsignedInt32::class,
    ];
}