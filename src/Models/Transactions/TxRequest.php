<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Transactions;

use XRPL_PHP\Models\BaseRequest;

class TxRequest extends BaseRequest
{
    protected string $command = "tx";

    public function __construct(
        protected string $transaction, //256-bit hash of transaction as hex
        protected ?bool $binary = null
    ) {}
}