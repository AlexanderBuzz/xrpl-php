<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Methods;

use XRPL_PHP\Models\BaseResponse;

class PingResponse extends BaseResponse
{
    public function __construct(
        array $result
    ) {
        $this->result = $result;

        parent::__construct();
    }
}