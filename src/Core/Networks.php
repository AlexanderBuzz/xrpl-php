<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Core;

use Exception;

class Networks
{
    private const NETWORKS = [
        'mainnet' => [
            'label' => 'XRPL Mainnet',
            'jsonRpcUrl' => 'https://s1.ripple.com:51234',
            'wsUrl' => 'wss://s1.ripple.com',
            'networkId' => 0
        ],
        'testnet' => [
            'label' => 'XRPL Testnet',
            'jsonRpcUrl' => 'https://s.altnet.rippletest.net:51234',
            'wsUrl' => 'wss://s.altnet.rippletest.net:51233',
            'networkId' => 1
        ],
        'devnet' => [
            'label' => 'XRPL Devnet',
            'jsonRpcUrl' => 'wss://s.devnet.rippletest.net:51234',
            'wsUrl' => 'wss://s.devnet.rippletest.net:51233',
            'networkId' => 2
        ],
        'xahau_mainnet' => [
            'label' => 'Xahau Mainnet',
            'jsonRpcUrl' => 'https://xahau.network',
            'wsUrl' => 'wss://xahau.network',
            'networkId' => 21337
        ],
        'xahau_testnet' => [
            'label' => 'Xahau Testnet',
            'jsonRpcUrl' => 'https://xahau-test.net',
            'wsUrl' => 'wss://xahau-test.net',
            'networkId' => 21338
        ],
    ];

    /**
     * @param string $identifier
     * @return array
     * @throws Exception
     */
    public static function getNetwork(string $identifier): array
    {
        if (isset(self::NETWORKS[$identifier])) {
            return self::NETWORKS[$identifier];
        }

        throw new Exception('Network not found');
    }

    /**
     * @param int $networkId
     * @return array
     * @throws Exception
     */
    public static function getNetworkByNetworkId(int $networkId): array
    {
        foreach (self::NETWORKS as $network) {
            if ($networkId === $network['networkId']) {
                return $network;
            }
        }

        throw new Exception('Network not found');
    }
}