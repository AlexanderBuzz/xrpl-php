<?php

require __DIR__.'/../vendor/autoload.php';

use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Models\Account\AccountInfoRequest;
use XRPL_PHP\Models\Transactions\Payment;
use XRPL_PHP\Wallet\Wallet;

//$wallet = Wallet::

$testnetStandbyAccountSeed = 'sEd7r9n11TmibXPBNL3zEGE3aNcof9R';
$testnetStandbyAccountAddress = 'raKXrkYfbh4Uzqc481jTXbaKsWnW5XRMjp';

$testnetOperationalAccountSeed = 'sEdTbNivZjJXYN4GqkNFTYBaxtwmorM';
$testnetOperationalAccountAddress = 'rBfXsGX5V8jcyKaPMCTcPvfVQzb4nEQymz';

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

$standbyWallet = Wallet::fromSeed($testnetStandbyAccountSeed);

$payment = new Payment(
    amount: "100",
    destination: $testnetOperationalAccountAddress,
    invoiceId: "Test Payment"
);

$xrpBalanceRequest = new AccountInfoRequest($testnetStandbyAccountAddress);
$body = json_encode($xrpBalanceRequest->getBody());
$response = $client->rawSyncRequest('POST', '', $body);
$content = $content = $response->getBody()->getContents();
$json = json_decode($content, true);

$content = $response->getBody()->getContents();

print_r($content);

