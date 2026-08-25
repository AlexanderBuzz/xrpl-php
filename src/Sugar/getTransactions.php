<?php

namespace Hardcastle\XRPL_PHP\Sugar;

use Exception;
use Hardcastle\XRPL_PHP\Client\AccountReader;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;

/**
 * Thin wrapper around Hardcastle\XRPL_PHP\Client\AccountReader.
 */

if (! function_exists('Hardcastle\XRPL_PHP\Sugar\getTransactions')) {

    /**
     * @deprecated Use JsonRpcClient::getTransactions() or AccountReader::getTransactions()
     * @throws Exception
     */
    function getTransactions(
        JsonRpcClient $client,
        string $address,
        ?int $ledgerIndexMin = null,
        ?int $ledgerIndexMax = null,
        ?string $ledgerHash = null,
        ?string $ledgerIndex = 'validated',
        ?bool $binary = null,
        ?bool $forward = null,
        ?int $limit = null,
        mixed $marker = null
    ): array
    {
        return (new AccountReader($client))->getTransactions(
            $address, $ledgerIndexMin, $ledgerIndexMax, $ledgerHash,
            $ledgerIndex, $binary, $forward, $limit, $marker
        );
    }
}
