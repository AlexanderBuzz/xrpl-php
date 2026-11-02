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
 * Grants the account in Authorize permissions to send transactions on behalf
 * of Account (Permission Delegation, XLS-75; PermissionDelegationV1_1 is
 * enabled on Mainnet since 2026-10-08). At most ten Permission entries, each
 * with a PermissionValue the codec takes by name: a transaction type
 * ("Payment", "TrustSet"), which grants all of it, or a granular permission
 * ("AccountDomainSet", "TrustlineAuthorize"; the names and numbers are
 * Definitions::GRANULAR_PERMISSIONS), which grants one narrow use of one
 * type. A new list replaces the old one; an empty list revokes everything
 * and deletes the Delegate object. AccountSet, SetRegularKey, SignerListSet,
 * DelegateSet, AccountDelete, Batch and the pseudo-transactions cannot be
 * granted, nor can the not yet integrated types XLS-75 §8 lists; the ledger
 * refuses them with temMALFORMED, this library does not check.
 *
 * The delegate then sends a transaction with Account = the delegating
 * account and Delegate = its own address, autofilled and signed with sign()
 * like any transaction: it uses the delegating account's Sequence, the
 * delegate's key and pays the fee from the delegate's balance. A transaction
 * the permissions do not cover fails with terNO_DELEGATE_PERMISSION.
 *
 * Until fixCleanup3_4_0 is active, do not grant PaymentBurn (XLS-75 §8).
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
