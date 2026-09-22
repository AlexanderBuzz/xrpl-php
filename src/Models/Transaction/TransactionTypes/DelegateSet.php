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
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\StArray;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/delegateset.html
 *
 * This model exists so that a DelegateSet transaction found in the ledger
 * decodes and so that the field list mirrors definitions.json. Building one
 * with this library is possible but not supported yet:
 *
 * - each Permission entry carries a bare PermissionValue (UInt32), and the
 *   library has no constants for the transaction type and granular permission
 *   numbers behind it, so the caller has to look them up in rippled;
 * - sending a transaction on behalf of the delegating account (the Delegate
 *   field on the delegated transaction) is not handled by Wallet::sign().
 *
 * Permission Delegation (XLS-75) was rewritten alongside Batch and shipped in
 * rippled 3.3.0; it is not active on Mainnet. The permission constants and
 * the delegated signing path will follow once it is.
 *
 * https://github.com/XRPLF/XRPL-Standards/tree/master/XLS-0075-permission-delegation
 */
class DelegateSet extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'Authorize' => AccountId::class,
        'Permissions' => StArray::class,
    ];
}
