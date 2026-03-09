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

use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt32;

/**
 * OracleDelete transaction
 * https://xrpl.org/oracledelete.html
 */
class OracleDelete extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'OracleDocumentID' => UnsignedInt32::class,
    ];
}
