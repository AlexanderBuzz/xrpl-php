<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XRPL_PHP\Models\Utility;

use XRPL_PHP\Models\BaseRequest;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/json.html
 */
final class JsonRequest extends BaseRequest
{
    protected string $command = "json";

    protected array $body;

    /**
     * The json method is a proxy to running other commands, and accepts the parameters for the command
     * as a JSON value. It is exclusive to the Commandline client, and intended for cases where
     * the commandline syntax for specifying parameters is inadequate or undesirable.
     *
     * @param string $serializedJson
     */
    public function __construct(string $serializedJson) {
        $this->body = json_decode($serializedJson, true);
    }

    public function getBody(): array
    {
        return $this->body;
    }
}