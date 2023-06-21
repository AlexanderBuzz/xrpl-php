<?php declare(strict_types=1);

namespace XRPL_PHP\Models\ServerInfo;

use XRPL_PHP\Models\BaseRequest;

/**
 * public API Methods / Ledger Methods
 * https://xrpl.org/server_state.html
 */
class ServerStateRequest extends BaseRequest
{
    protected string $command = "server_state";
}