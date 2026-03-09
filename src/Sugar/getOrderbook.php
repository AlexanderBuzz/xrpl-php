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
use Hardcastle\XRPL_PHP\Models\PathOrderbook\BookOffersRequest;
use Hardcastle\XRPL_PHP\Models\ErrorResponse;

if (! function_exists('Hardcastle\XRPL_PHP\Sugar\getOrderbook')) {

    /**
     * @param JsonRpcClient $client
     * @param array $takerGets
     * @param array $takerPays
     * @param string|null $ledgerHash
     * @param string|null $ledgerIndex
     * @param int|null $limit
     * @param string|null $taker
     * @return array
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
        $request = new BookOffersRequest(
            takerGets: $takerGets,
            takerPays: $takerPays,
            ledgerHash: $ledgerHash,
            ledgerIndex: $ledgerIndex,
            number: $limit,
            taker: $taker
        );

        $response = $client->request($request)->wait();

        if ($response::class === ErrorResponse::class) {
            throw new Exception($response->getError());
        }

        return $response->getResult()['offers'];
    }
}
