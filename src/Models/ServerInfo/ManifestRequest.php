<?php declare(strict_types=1);

namespace XRPL_PHP\Models\ServerInfo;

use XRPL_PHP\Models\BaseRequest;

/**
 * public API Methods / Ledger Methods
 * https://xrpl.org/manifest.html
 */
class ManifestRequest extends BaseRequest
{
    protected string $command = "manifest";

    public function __construct(
        protected string $publicKey,
    ) {}
}