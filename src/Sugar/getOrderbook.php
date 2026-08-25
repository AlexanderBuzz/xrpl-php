<?php

namespace Hardcastle\XRPL_PHP\Sugar;

use Exception;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Client\OrderbookReader;

/**
 * Thin wrapper around Hardcastle\XRPL_PHP\Client\OrderbookReader.
 */

if (! function_exists('Hardcastle\XRPL_PHP\Sugar\getOrderbook')) {

    /**
     * @deprecated Use JsonRpcClient::getOrderbook() or OrderbookReader::getOrderbook()
     * @throws Exception
     */
    function getOrderbook(
        JsonRpcClient $client,
        array $takerGets,
        array $takerPays,
        ?string $ledgerHash = null,
        ?string $ledgerIndex = 'validated',
        ?int $limit = null,
        ?string $taker = null
    ): array
    {
        return (new OrderbookReader($client))->getOrderbook(
            $takerGets, $takerPays, $ledgerHash, $ledgerIndex, $limit, $taker
        );
    }
}
