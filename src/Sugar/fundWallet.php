<?php

namespace XRPL_PHP\Sugar;

use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\Utilities;
use XRPL_PHP\Wallet\Wallet;

    const FAUCET_NETWORK = [
        'Testnet' => 'https://faucet.altnet.rippletest.net:443',
        'Devnet' => 'https://faucet.devnet.rippletest.net:443',
        'NFTDevnet' => 'https://faucet-nft.ripple.com:443',
    ];

    function getHttpOptions(JsonRpcClient $client, Buffer $postBody, ?string $faucetHost): array
    {
        return [
            'hostname' => $faucetHost ?? getFaucetHost($client),
            'port' => 443,
            'path' => '/accounts',
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'Content-Length' => $postBody->getLength()
            ]
        ];
    }

    function getFaucetHost(JsonRpcClient $client): string
    {
        $connectionUrl = $client->getConnectionUrl();

        // 'altnet' for Ripple Testnet server and 'testnet' for XRPL Labs Testnet server
        if(str_contains($connectionUrl, 'altnet') || str_contains($connectionUrl, 'testnet')) {
            return FAUCET_NETWORK['Testnet'];
        }

        if(str_contains($connectionUrl, 'devnet')) {
            return FAUCET_NETWORK['Devnet'];
        }

        if(str_contains($connectionUrl, 'xls20-sandbox')) {
            return FAUCET_NETWORK['NFTDevnet'];
        }

        throw new Exception('Faucet URL is not defined or inferrable.');
    }

    function getUpdatedBalance(JsonRpcClient $client, string $address, float $originalBalance): float
    {
        $newBalance = null;
        try {
            $newBalance = (float) $client->getXrpBalance($address);
        } catch (Exception $e) {
            //new Balance remains undefined
        }

        if ($newBalance > $originalBalance) {

        }

        //resolve: (response: { wallet: Wallet; balance: number }) => void,
        //reject: (err: ErrorConstructor | Error | unknown) => void,

        return 0;
    }

    if (! function_exists('XRPL_PHP\Sugar\fundWallet')) {

        function fundWallet(
            JsonRpcClient $client,
            ?Wallet $wallet = null,
            ?string $faucetHost = null
        ): array
        {
            // Generate a new Wallet if no existing Wallet is provided or its address is invalid to fund
            if ($wallet && Utilities::isValidClassicAddress($wallet->getClassicAddress())) {
                $walletToFund = $wallet;
            } else {
                $walletToFund = Wallet::generate();
            }

            // Create the POST request body
            $jsonData = json_encode(['destination' => $walletToFund->getClassicAddress()]);

            $startingBalance = 0;
            try {
                $startingBalance = getXrpBalance($client, $walletToFund->getClassicAddress());
            } catch (Exception $e) {
                // startingBalance remains '0'
            }

            if (!is_null($faucetHost)) {
                $faucetClient = new JsonRpcClient($faucetHost);
            } else {
                $faucetClient = new JsonRpcClient(getFaucetHost($client));
            }

            $response = $faucetClient->rawRequest(
                method: 'POST',
                resource: '/accounts',
                body: $jsonData
            )->wait();

            //TODO: check status code and content type
            $faucetWallet = json_decode($response->getBody(), true);

            if (!isset($faucetWallet['account']['address'])) {

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
                    sleep($intervalSeconds);
                    echo $attempts . PHP_EOL;
                    $attempts--;
                }
            }

            return [
                'wallet' => $walletToFund,
                'balance' => $updatedBalance,
                'fundWalletResponse' => json_decode($response->getBody(), true)
            ];
        }
    }