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
