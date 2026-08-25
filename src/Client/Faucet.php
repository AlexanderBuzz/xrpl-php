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
use Hardcastle\XRPL_PHP\Core\CoreUtilities;
use Hardcastle\XRPL_PHP\Wallet\DefaultFaucets;
use Hardcastle\XRPL_PHP\Wallet\Wallet;

/**
 * Funds a wallet from a test network faucet.
 *
 * This is the object form of Sugar\fundWallet(), which stays available as a
 * deprecated wrapper.
 */
class Faucet
{
    /** Seconds between balance polls after asking the faucet */
    public const POLL_INTERVAL = 1;

    /** How often the balance is polled before giving up */
    public const POLL_ATTEMPTS = 20;

    public function __construct(private readonly JsonRpcClient $client)
    {
    }

    /**
     * Ask the faucet for funds and wait until the balance reflects them.
     *
     * @param Wallet|null $wallet Funded if given and valid, otherwise a new one is generated
     * @param string|null $faucetHost
     * @param string|null $faucetPath
     * @param string|null $amount
     * @return array{wallet: Wallet, balance: float, fundWalletResponse: array}
     * @throws Exception
     */
    public function fundWallet(
        ?Wallet $wallet = null,
        ?string $faucetHost = null,
        ?string $faucetPath = null,
        ?string $amount = null
    ): array {
        $walletToFund = ($wallet && CoreUtilities::isValidClassicAddress($wallet->getClassicAddress()))
            ? $wallet
            : Wallet::generate();

        $accountReader = new AccountReader($this->client);

        $startingBalance = 0.0;
        try {
            $startingBalance = (float)$accountReader->getXrpBalance($walletToFund->getClassicAddress());
        } catch (Exception) {
            // An unfunded account does not exist yet, so its balance is zero
        }

        $hostname = $faucetHost ?? DefaultFaucets::getFaucetHost($this->client);
        $pathname = $faucetPath ?? DefaultFaucets::getDefaultFaucetPath($hostname);

        $response = (new JsonRpcClient($hostname))->rawRequest(
            method: 'POST',
            resource: $pathname,
            body: json_encode([
                'destination' => $walletToFund->getClassicAddress(),
                'xrpAmount' => $amount ?? '100',
            ])
        )->wait();

        $faucetResponse = json_decode((string)$response->getBody(), true);

        if (!isset($faucetResponse['account']['address'])) {
            throw new Exception('The faucet did not return an account address.');
        }

        return [
            'wallet' => $walletToFund,
            'balance' => $this->waitForFunding($faucetResponse['account']['address'], $startingBalance),
            'fundWalletResponse' => $faucetResponse,
        ];
    }

    /**
     * Poll the balance until the faucet payment shows up.
     *
     * @param string $address
     * @param float $startingBalance
     * @return float The balance last seen, funded or not
     */
    private function waitForFunding(string $address, float $startingBalance): float
    {
        $accountReader = new AccountReader($this->client);
        $balance = $startingBalance;

        for ($attempt = 0; $attempt < self::POLL_ATTEMPTS; $attempt++) {
            try {
                $balance = (float)$accountReader->getXrpBalance($address);
                if ($balance > $startingBalance) {
                    return $balance;
                }
            } catch (Exception) {
                // The account may not exist yet
            }

            sleep(self::POLL_INTERVAL);
        }

        return $balance;
    }
}
