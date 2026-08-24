<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes;

use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\AccountId;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Blob;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Hash192;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Hash256;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt16;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt32;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/mptokenissuanceset.html
 */
class MPTokenIssuanceSet extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'MPTokenIssuanceID' => Hash192::class,
        'Holder' => AccountId::class,
        'DomainID' => Hash256::class,
        'MPTokenMetadata' => Blob::class,
        'TransferFee' => UnsignedInt16::class,
        'MutableFlags' => UnsignedInt32::class,
    ];
}
