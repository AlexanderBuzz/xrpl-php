<?php

namespace Hardcastle\XRPL_PHP\Sugar;

use Exception;
use Hardcastle\XRPL_PHP\Client\AccountReader;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;

/**
 * Thin wrappers around Hardcastle\XRPL_PHP\Client\AccountReader.
 *
 * The logic moved into that class; these functions remain so that existing
 * code keeps working. They will be removed in a future major version.
 */

if (! function_exists('Hardcastle\XRPL_PHP\Sugar\getXrpBalance')) {

    /**
     * @deprecated Use JsonRpcClient::getXrpBalance() or AccountReader::getXrpBalance()
     * @throws Exception
     */
    function getXrpBalance(
        JsonRpcClient $client,
        string $address,
        ?string $ledgerHash = null,
        ?string $ledgerIndex = 'validated',
    ): string
    {
        return (new AccountReader($client))->getXrpBalance($address, $ledgerHash, $ledgerIndex);
    }
}

if (! function_exists('Hardcastle\XRPL_PHP\Sugar\getBalances')) {

    /**
     * @deprecated Use JsonRpcClient::getBalances() or AccountReader::getBalances()
     * @throws Exception
     */
    function getBalances(
        JsonRpcClient $client,
        string $address,
        ?string $ledgerHash = null,
        ?string $ledgerIndex = 'validated',
        ?string $peer = null,
        ?int $limit = null
    ): array
    {
        return (new AccountReader($client))->getBalances($address, $ledgerHash, $ledgerIndex, $peer, $limit);
    }
}
