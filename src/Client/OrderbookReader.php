<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Client;

use Exception;
use Hardcastle\XRPL_PHP\Models\ErrorResponse;
use Hardcastle\XRPL_PHP\Models\PathOrderbook\BookOffersRequest;

/**
 * Reads the decentralized exchange order book.
 *
 * This is the object form of Sugar\getOrderbook(), which stays available as a
 * deprecated wrapper.
 */
class OrderbookReader
{
    public function __construct(private readonly JsonRpcClient $client)
    {
    }

    /**
     * The offers standing in one order book.
     *
     * @throws Exception
     */
    public function getOrderbook(
        array $takerGets,
        array $takerPays,
        ?string $ledgerHash = null,
        ?string $ledgerIndex = 'validated',
        ?int $limit = null,
        ?string $taker = null
    ): array {
        $response = $this->client->request(new BookOffersRequest(
            takerGets: $takerGets,
            takerPays: $takerPays,
            ledgerHash: $ledgerHash,
            ledgerIndex: $ledgerIndex,
            number: $limit,
            taker: $taker
        ))->wait();

        if ($response instanceof ErrorResponse) {
            throw new Exception($response->getError());
        }

        return $response->getResult()['offers'];
    }
}
