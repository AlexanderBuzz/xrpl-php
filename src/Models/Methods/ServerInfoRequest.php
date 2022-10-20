<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Methods;

use XRPL_PHP\Models\BaseRequest;

final class ServerInfoRequest extends BaseRequest
{
    protected string $command = "server_info";
}