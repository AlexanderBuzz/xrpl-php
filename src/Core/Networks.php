<?php

namespace XRPL_PHP\Core;

class Networks
{
    private const NETWORKS = [
        'mainnet' => [
            'jsonRpcUrl' => 'https://s1.ripple.com:51234',
            'wsUrl' => 'wss://s1.ripple.com',
            'networkId' => 0
        ],
        'testnet' => [
            'jsonRpcUrl' => 'https://s.altnet.rippletest.net:51234',
            'wsUrl' => 'wss://s.altnet.rippletest.net:51233',
            'networkId' => 1
        ],
        'devnet' => [
            'jsonRpcUrl' => 'wss://s.devnet.rippletest.net:51234',
            'wsUrl' => 'wss://s.devnet.rippletest.net:51233',
            'networkId' => 2
        ]
    ];

    /**
     * @param string $identifier
     * @return array
     * @throws \Exception
     */
    public static function getNetwork(string $identifier): array
    {
        if (isset(self::NETWORKS[$identifier])) {
            return self::NETWORKS[$identifier];
        }

        throw new \Exception('Network not found');
    }

    /**
     * @param int $networkId
     * @return array
     * @throws \Exception
     */
    public static function getNetworkByNetworkId(int $networkId): array
    {
        foreach (self::NETWORKS as $network) {
            if ($networkId === $network['networkId']) {
                return $network;
            }
        }

        throw new \Exception('Network not found');
    }
}