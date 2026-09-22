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
 * public API Methods / Transaction Methods
 * https://xrpl.org/batch.html
 *
 * This model exists so that a Batch transaction found in the ledger decodes and
 * so that the field list mirrors definitions.json. Building and signing a Batch
 * with this library is NOT supported.
 *
 * Batch (XLS-56) was withdrawn in rippled 3.1.1 after a signature check bug and
 * came back rewritten as Batch V1.1 in rippled 3.3.0. The rewrite changed what
 * a client has to do before signing, and none of it is implemented here:
 *
 * - every inner transaction carries the tfInnerBatchTxn flag and an empty
 *   SigningPubKey and TxnSignature, and is never signed on its own;
 * - each entry in BatchSigners signs a payload that binds the outer Account,
 *   so a signature over the inner transactions alone is rejected;
 * - BatchSigners must be in the order rippled enforces.
 *
 * Wallet::sign() knows nothing of this. A Batch it signs is well formed and
 * fails at the node, which reports temDISABLED for as long as the amendment is
 * not active and hides the real cause. The helper will be added once Batch is
 * enabled on Mainnet and its wire format is final; until then use xrpl.js
 * (signMultiBatch / combineBatchSigners) for the signing step.
 *
 * https://github.com/XRPLF/XRPL-Standards/tree/master/XLS-0056-batch
 */
class Batch extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'RawTransactions' => StArray::class,
        'BatchSigners' => StArray::class,
    ];
}
