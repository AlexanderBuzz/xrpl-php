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

    if (! function_exists('XRPL_PHP\Sugar\fundWallet')) {

        function fundWallet(
            JsonRpcClient $client,
            ?Wallet $wallet = null,
            ?string $faucetHost = null
        ): PromiseInterface
        {
            // Generate a new Wallet if no existing Wallet is provided or its address is invalid to fund
            if ($wallet && Utilities::isValidClassicAddress($wallet->getClassicAddress())) {
                $walletToFund = $wallet;
            } else {
                $walletToFund = Wallet::generate();
            }

            // Create the POST request body
            $jsonData = json_encode(['destination' => $walletToFund->getClassicAddress()]);
            //$postBody = Buffer::from($jsonData);

            $startingBalance = 0;

            try {
                $startingBalance = getXrpBalance($client, $walletToFund->getClassicAddress());
            } catch (Exception $e) {
                $test =1;
                // startingBalance remains '0'
            }

            // Options to pass to https.request

            //$httpOptions = getHttpOptions($client, $postBody, $faucetHost);

            if (!is_null($faucetHost)) {
                $faucetClient = new JsonRpcClient($faucetHost);
            } else {
                $faucetClient = new JsonRpcClient(getFaucetHost($client));
            }

            $response = $faucetClient->rawRequest(
                method: 'POST',
                resource: '/accounts',
                body: $jsonData
            );

            return $response;
        }
    }