<?php declare(strict_types=1);

namespace XRPL_PHP\Wallet;

use Exception;
use XRPL_PHP\Client\JsonRpcClient;

class DefaultFaucets
{
    const FAUCET_NETWORK = [
        'Testnet' => 'https://faucet.altnet.rippletest.net:443',
        'Devnet' => 'https://faucet.devnet.rippletest.net:443',
        'AMMDevnet' => 'https://ammfaucet.devnet.rippletest.net:443',
        'NFTDevnet' => 'https://faucet-nft.ripple.com:443',
        'HooksV2Testnet' => 'https://hooks-testnet-v2.xrpl-labs.com:443',
    ];

    const FAUCET_NETWORK_PATHS = [
        'Testnet' => '/accounts',
        'Devnet' => '/accounts',
        'AMMDevnet' => '/accounts',
        'NFTDevnet' => '/accounts',
        'HooksV2Testnet' => '/accounts',
    ];

    /**
     * Get the faucet host based on the Client connection.
     *
     * @param JsonRpcClient $client
     * @return string
     * @throws Exception
     */
    public static function getFaucetHost(JsonRpcClient $client): string
    {
        $connectionUrl = $client->getConnectionUrl();

        if (str_contains($connectionUrl, 'hooks-testnet-v2')) {
            return self::FAUCET_NETWORK['HooksV2Testnet'];
        }

        // 'altnet' for Ripple Testnet server and 'testnet' for XRPL Labs Testnet server
        if (str_contains($connectionUrl, 'altnet') || str_contains($connectionUrl, 'testnet')) {
            return self::FAUCET_NETWORK['Testnet'];
        }

        if (str_contains($connectionUrl, 'amm')) {
            return self::FAUCET_NETWORK['AMMDevnet'];
        }

        if (str_contains($connectionUrl, 'devnet')) {
            return self::FAUCET_NETWORK['Devnet'];
        }

        if (str_contains($connectionUrl, 'xls20-sandbox')) {
            return self::FAUCET_NETWORK['NFTDevnet'];
        }

        throw new Exception('Faucet URL is not defined or inferrable.');
    }

    /**
     * Get the faucet pathname based on the faucet hostname.
     *
     * @param string $hostname
     * @return string
     */
    public static function getDefaultFaucetPath(string $hostname): string
    {
        $key = array_search($hostname, self::FAUCET_NETWORK);
        return self::FAUCET_NETWORK_PATHS[$key] ?? '/accounts';
    }
}