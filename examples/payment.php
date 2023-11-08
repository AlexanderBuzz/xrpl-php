<?php

require __DIR__.'/../vendor/autoload.php';

use Codedungeon\PHPCliColors\Color;
use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Wallet\Wallet;
use function XRPL_PHP\Sugar\xrpToDrops;

/**
 * This script can be used with the examples from
 * https://learn.xrpl.org/course/code-with-the-xrpl/lesson/create-accounts-and-send-xrp/
 *
 * Note that the Testnet gets reset regularly, so the given addresses may be
 * out of date by the time you are using this example. Just generate new ones
 * by using this the above link or directly at https://xrpl.org/xrp-testnet-faucet.html
 *
 * Purpose: Rebuild the "Send XRP" function from the JavaScript examples
 *
 * Warning: At some point, the XRP balance may be empty if this script gets called often enough,
 * so create your own wallets, with the quickstart example or fundWallet here...
 */

// Use your own credentials here:
$aliceAccountSeed = 'sEdTcvQ9k4UUEHD9y947QiXEs93Fp2k';
$aliceWallet = Wallet::fromSeed($aliceAccountSeed);

// Use your own credentials here:
$bobAccountSeed = 'sEdVHf8rNEaRveJw4NdVKxm3iYWFuRb';
$bobWallet = Wallet::fromSeed($bobAccountSeed);

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

$xrpAmount = '100';
print_r(Color::YELLOW . "Sending {$xrpAmount} XRP from standby wallet to operational wallet, please wait..." . PHP_EOL);
$paymentTx = [
    "TransactionType" => "Payment",
    "Account" => $aliceWallet->getAddress(),
    "Amount" => xrpToDrops($xrpAmount),
    "Destination" => $bobWallet->getAddress()
];
$preparedTx = $client->autofill($paymentTx);
$signedTx = $aliceWallet->sign($preparedTx);

$txResponse = $client->submitAndWait($signedTx['tx_blob']);
$result = $txResponse->getResult();
if ($result['meta']['TransactionResult'] === 'tecUNFUNDED_PAYMENT') {
    print_r(Color::RED . "Error: The sending account is unfunded! TxHash: " . Color::RESET . "{$result['hash']}" . PHP_EOL . PHP_EOL);
} else {
    print_r(Color::GREEN . "Token payment done! TxHash: " . Color::WHITE . "{$result['hash']}" . PHP_EOL . PHP_EOL);
}

print_r(Color::RESET . "You can check wallets/accounts and transactions on https://test.bithomp.com"  . PHP_EOL . PHP_EOL);
