<?php

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
