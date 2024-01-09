<?php

require __DIR__.'/../vendor/autoload.php';

use Codedungeon\PHPCliColors\Color;
use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Wallet\Wallet;
use function Hardcastle\XRPL_PHP\Sugar\xrpToDrops;

$testnetStandbyAccountSeed = 'sEd7rnfWxwJmRditu2UpSsrZDRgtctn';
$standbyWallet = Wallet::fromSeed($testnetStandbyAccountSeed);

$testnetOperationalAccountSeed = 'sEd7XWKCuXENqLjPopq6WWFvoTnG3dX';
$operationalWallet = Wallet::fromSeed($testnetOperationalAccountSeed);

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

$xrpAmount = '50';
print_r(Color::YELLOW . "Sending {$xrpAmount} XRP from standby wallet to operational wallet, please wait..." . PHP_EOL);
$tx = [
    "TransactionType" => "Payment",
    "Account" => $standbyWallet->getAddress(),
    "Amount" => xrpToDrops($xrpAmount),
    "Destination" => $operationalWallet->getAddress(),
    "DestinationTag" => 1937215245
];
$autofilledTx = $client->autofill($tx);
print_r($tx);
print_r(PHP_EOL. PHP_EOL);
print_r($autofilledTx);
$signedTx = $standbyWallet->sign($autofilledTx);

$txResponse = $client->submitAndWait($signedTx['tx_blob']);
$result = $txResponse->getResult();
if ($result['meta']['TransactionResult'] === 'tecUNFUNDED_PAYMENT') {
    print_r(Color::RED . "Error: The sending account is unfunded! TxHash: " . Color::RESET . "{$result['hash']}" . PHP_EOL . PHP_EOL);
} else {
    print_r(Color::GREEN . "Token payment done! TxHash: " . Color::WHITE . "{$result['hash']}" . PHP_EOL . PHP_EOL);
}

print_r(Color::RESET . "You can check wallets/accounts and transactions on https://test.bithomp.com"  . PHP_EOL . PHP_EOL);
