<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Methods;

use XRPL_PHP\Models\BaseRequest;
use XRPL_PHP\Models\Transactions\Hash256;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/submit.html
 */
class SubmitRequest extends BaseRequest
{
    protected string $command = "submit";

    public function __construct(
        protected string $tx_blob,
        protected bool $fail_hard = false
    ) {}
}