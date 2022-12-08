<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Transaction\TransactionTypes;

use XRPL_PHP\Core\RippleBinaryCodec\Types\Blob;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Hash128;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Hash256;
use XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt32;
use XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt8;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/accountset.html
 */
class AccountSet extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'ClearFlag' => UnsignedInt32::class,
        'Domain' => Blob::class,
        'EmailHash' => Hash128::class,
        'MessageKey' => Blob::class,
        'NFTokenMinter' => Blob::class,
        'SetFlag' => UnsignedInt32::class,
        'TransferRate' => UnsignedInt32::class,
        'TickSize' => UnsignedInt8::class,
        'WalletLocator' => Hash256::class,
        'WalletSize' => UnsignedInt32::class
    ];
}