<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Transaction\TransactionTypes;

use XRPL_PHP\Core\RippleBinaryCodec\Types\StArray;
use XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt32;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/ticketcreate.html
 */
class TicketCreate extends BaseTransaction
{
    protected array $transactionTypeProperties = [
        'TicketCount' => UnsignedInt32::class
    ];
}