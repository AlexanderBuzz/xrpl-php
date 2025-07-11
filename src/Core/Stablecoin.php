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

class Stablecoin {
    private const RLUSD = [
        'mainnet' => [
            'issuer' => 'rMxCKbEDwqr76QuheSUMdEGf4B9xJ8m5De',
            'currency' => '524C555344000000000000000000000000000000',
        ],
        'testnet' => [
            'issuer' => 'rQhWct2fv4Vc4KRjRgMrxa8xPN9Zx9iLKV',
            'currency' => '524C555344000000000000000000000000000000',
        ],
    ];

    /**
     * Returns the RLUSD stablecoin information for the specified network.
     *
     * @param string $network The network type (e.g., 'mainnet', 'testnet').
     * @return array An array containing the issuer and currency of RLUSD.
     * @throws Exception If the network is not supported.
     */
    public static function getRlusdSettings(string $network): array
    {
        if (isset(self::RLUSD[$network])) {
            return self::RLUSD[$network];
        }

        throw new Exception('RLUSD not available for network: ' . $network);
    }

    /**
     * Convenience function returns an object of type Amount for RLUSD.
     *
     * @param string $network The network type (e.g., 'mainnet', 'testnet').
     * @param string $value The value of the RLUSD amount.
     * @return array An array containing the currency, issuer, and value.
     * @throws Exception If the network is not supported.
     */
    public static function getRLUSDAmount(string $network, string $value): array
    {
        $rlusd = self::getRlusdSettings($network);

        return [
            'currency' => $rlusd['currency'],
            'issuer' => $rlusd['issuer'],
            'value' => $value,
        ];
    }
}
