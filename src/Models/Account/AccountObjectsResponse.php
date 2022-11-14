<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Account;

use XRPL_PHP\Models\BaseResponse;

/**
 * public API Methods / Ledger Methods
 * https://xrpl.org/account_objects.html
 */
class AccountObjectsResponse extends BaseResponse
{
    public function __construct(
        array $result
    ) {
        $this->result = $result;

        parent::__construct();
    }
}