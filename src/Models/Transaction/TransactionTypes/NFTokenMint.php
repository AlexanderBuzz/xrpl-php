<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Transaction\TransactionTypes;

use XRPL_PHP\Core\RippleBinaryCodec\Types\AccountId;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Amount;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Blob;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Hash256;
use XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt16;
use XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt32;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/nftokenmint.html
 */
class NFTokenMint extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'NFTokenTaxon' => UnsignedInt32::class,
        'Issuer' => AccountId::class,
        'TransferFee' => UnsignedInt16::class,
        'URI' => Blob::class,
    ];
}