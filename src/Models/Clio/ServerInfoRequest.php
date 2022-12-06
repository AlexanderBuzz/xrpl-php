<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Clio;

use XRPL_PHP\Models\BaseRequest;

/**
 * public API Methods / Clio Methods
 * https://xrpl.org/server_info-clio.html
 */

final class ServerInfoRequest extends BaseRequest
{
    protected string $command = "server_info";
}