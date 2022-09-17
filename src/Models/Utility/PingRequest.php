<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Utility;

use XRPL_PHP\Models\BaseRequest;

final class PingRequest extends BaseRequest
{
    protected string $command = "ping";
}