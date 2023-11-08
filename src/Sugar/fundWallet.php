<?php

namespace XRPL_PHP\Sugar;

use Exception;
use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\CoreUtilities;
use XRPL_PHP\Wallet\DefaultFaucets;
use XRPL_PHP\Wallet\Wallet;

function getHttpOptions(JsonRpcClient $client, Buffer $postBody, ?string $faucetHost): array
{
    return [
        'hostname' => $faucetHost ?? DefaultFaucets::getFaucetHost($client),
        'port' => 443,
        'path' => '/accounts',
        'method' => 'POST',
        'headers' => [
            'Content-Type' => 'application/json',
            'Content-Length' => $postBody->getLength()
        ]
    ];
}

function getUpdatedBalance(JsonRpcClient $client, string $address, float $originalBalance): float
{
    $newBalance = null;
    try {
        $newBalance = (float)$client->getXrpBalance($address);
    } catch (Exception $e) {
        //new Balance remains undefined
    }

    if ($newBalance > $originalBalance) {

    }

    //resolve: (response: { wallet: Wallet; balance: number }) => void,
    //reject: (err: ErrorConstructor | Error | unknown) => void,

    return 0;
}

if (!function_exists('XRPL_PHP\Sugar\fundWallet')) {

    function fundWallet(
        JsonRpcClient $client,
        ?Wallet       $wallet = null,
        ?string       $faucetHost = null,
        ?string       $faucetPath = null,
        ?string       $amount = null
    ): array
    {
        // Generate a new Wallet if no existing Wallet is provided or its address is invalid to fund
        if ($wallet && CoreUtilities::isValidClassicAddress($wallet->getClassicAddress())) {
            $walletToFund = $wallet;
        } else {
            $walletToFund = Wallet::generate();
        }

        // Create the POST request body
        $jsonData = json_encode([
            'destination' => $walletToFund->getClassicAddress(),
            'xrpAmount' => $amount
        ]);

        $startingBalance = 0;
        try {
            $startingBalance = getXrpBalance($client, $walletToFund->getClassicAddress());
        } catch (Exception $e) {
            // startingBalance remains '0'
        }

        // This would be getHTTPOptions in xrpl.js
        $hostname = $faucetHost ?? DefaultFaucets::getFaucetHost($client);
        $pathname = $faucetPath ?? DefaultFaucets::getDefaultFaucetPath($hostname);
        $faucetClient = new JsonRpcClient($hostname);

        $response = $faucetClient->rawRequest(
            method: 'POST',
            resource: $pathname,
            body: $jsonData
        )->wait();

        $faucetWallet = json_decode($response->getBody(), true);

        if (!isset($faucetWallet['account']['address'])) {
            // error: 'The faucet account is undefined'
        }

        $classicAddress = $faucetWallet['account']['address'];

        $updatedBalance = $startingBalance;

        $intervalSeconds = 1;
        $attempts = 20;
        while ($attempts > 0) {
            try {
                $updatedBalance = (float) getXrpBalance($client, $classicAddress);
                if ($updatedBalance > $startingBalance) {
                    break;
                }
            } catch (Exception $e) {

            }
            sleep($intervalSeconds);
            $attempts--;
        }

        return [
            'wallet' => $walletToFund,
            'balance' => $updatedBalance,
            'fundWalletResponse' => json_decode($response->getBody(), true)
        ];
    }
}