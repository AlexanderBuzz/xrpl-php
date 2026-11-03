<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Sugar;

use Exception;
use Hardcastle\XRPL_PHP\Client\FeeCalculator;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;

/**
 * Thin wrapper around Hardcastle\XRPL_PHP\Client\FeeCalculator.
 */

if (! function_exists('Hardcastle\XRPL_PHP\Sugar\getFeeXrp')) {

    /**
     * @deprecated Use JsonRpcClient::getFeeXrp() or FeeCalculator::getFeeXrp()
     * @throws Exception
     */
    function getFeeXrp(JsonRpcClient $client, ?int $cushion = null): string
    {
        return (new FeeCalculator($client))->getFeeXrp($cushion === null ? null : (float)$cushion);
    }
}
