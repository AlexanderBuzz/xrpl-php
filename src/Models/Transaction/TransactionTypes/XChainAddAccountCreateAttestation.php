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
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Amount;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\Blob;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt8;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\XchainBridge;

/**
 * XChainAddAccountCreateAttestation transaction
 * https://xrpl.org/xchainaddaccountcreateattestation.html
 */
class XChainAddAccountCreateAttestation extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'XChainBridge' => XchainBridge::class,
        'PublicKey' => Blob::class,
        'Signature' => Blob::class,
        'OtherChainSource' => AccountId::class,
        'Amount' => Amount::class,
        'AttestationRewardAccount' => AccountId::class,
        'AttestationSignerAccount' => AccountId::class,
        'WasLockingChainSend' => UnsignedInt8::class,
        'Destination' => AccountId::class,
    ];
}
