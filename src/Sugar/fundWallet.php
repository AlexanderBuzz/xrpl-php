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
use Hardcastle\XRPL_PHP\Client\Faucet;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Wallet\Wallet;

/**
 * Thin wrapper around Hardcastle\XRPL_PHP\Client\Faucet.
 */

if (!function_exists('Hardcastle\XRPL_PHP\Sugar\fundWallet')) {

    /**
     * @deprecated Use JsonRpcClient::fundWallet() or Faucet::fundWallet()
     * @throws Exception
     */
    function fundWallet(
        JsonRpcClient $client,
        ?Wallet       $wallet = null,
        ?string       $faucetHost = null,
        ?string       $faucetPath = null,
        ?string       $amount = null
    ): array
    {
        return (new Faucet($client))->fundWallet($wallet, $faucetHost, $faucetPath, $amount);
    }
}
