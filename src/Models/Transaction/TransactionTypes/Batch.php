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

use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\StArray;

/**
 * Batch: two to eight transactions applied together, in one of four modes
 * (BatchFlags): all or nothing, only one, until the first failure, or
 * independently. XLS-56, Batch V1.1, enabled on Mainnet since 2026-10-09.
 *
 * RawTransactions holds the inner transactions, each wrapped as
 * ['RawTransaction' => [...]], each with the tfInnerBatchTxn flag, Fee "0",
 * an empty SigningPubKey and no signature; the outer transaction pays and
 * signs for all of them. autofill() fills those in, sequences the inner
 * transactions per account and sets the fee (two base fees, one more per
 * signature in BatchSigners, plus what each inner transaction would cost).
 *
 * A Batch of one account is signed with Wallet::sign() like any transaction.
 * A Batch with inner transactions of other accounts needs BatchSigners: every
 * such account (or the delegate of a delegated inner transaction) signs the
 * complete, autofilled Batch with Wallet::signBatch(), the entries are merged
 * with Wallet::combineBatchSigners(), and the outer Account signs and submits.
 * The entries sign over the outer Account, sequence value, Flags and the inner
 * transaction IDs (BinaryCodec::encodeForSigningBatch()), so nothing may change
 * after the first signature. Applied inner transactions are transactions of
 * their own in the ledger; their metadata names the Batch in ParentBatchID.
 *
 * https://xrpl.org/docs/references/protocol/transactions/types/batch
 * https://github.com/XRPLF/XRPL-Standards/tree/master/XLS-0056-batch
 */
class Batch extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'RawTransactions' => StArray::class,
        'BatchSigners' => StArray::class,
    ];
}
