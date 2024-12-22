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
            'currency' => 'USD',
        ],
        'testnet' => [
            'issuer' => 'rQhWct2fv4Vc4KRjRgMrxa8xPN9Zx9iLKV',
            'currency' => 'USD',
        ],
    ];

    public static function getRLUSD(string $network): array
    {
        if (isset(self::RLUSD[$network])) {
            return self::RLUSD[$network];
        }

        throw new Exception('RLUSD not available for network: ' . $network);
    }

    public static function getRLUSDAmount(string $network, string $value): array
    {
        $rlusd = self::getRLUSD($network);

        return [
            'currency' => $rlusd['currency'],
            'issuer' => $rlusd['issuer'],
            'value' => $value,
        ];
    }
}
