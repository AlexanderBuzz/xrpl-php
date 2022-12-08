<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Transaction;

use XRPL_PHP\Models\BaseRequest;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/tx_history.html
 */
class TxHistoryRequest extends BaseRequest
{
    protected string $command = "tx_history";

    public function __construct(
        protected int $start
    ) {}
}