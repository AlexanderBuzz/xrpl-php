<?php

require __DIR__.'/../vendor/autoload.php';

use XRPL_PHP\Client\JsonRpcClient;

use XRPL_PHP\Wallet\Wallet;
use function XRPL_PHP\Sugar\autofill;
use function XRPL_PHP\Sugar\xrpToDrops;

/**
 * This script can be used with the examples from
 * https://live-xrpl.pantheonsite.io/course/code-with-the-xrpl/lesson/create-accounts-and-send-xrp/
 *
 * Note that the TesNet gets reset regularly, so the given addresses may be
 * out of date by the time you are using this example. Just generate new ones
 * by using the above link
 *
 * Purpose: Showcase the "Get Accounts From Seed" function from the JavaScript examples
 */

$testnetStandbyAccountSeed = 'sEd7FjgfnVmTnjvtQBe9qy2SYydiRqz';
$testnetStandbyAccountAddress = 'r9yqc2rDs43YLX6uAy6fhdZs8FKykNS6zC';
$standbyWallet = Wallet::fromSeed($testnetStandbyAccountSeed);

$testnetOperationalAccountSeed = 'sEdSgEUuDQXSCH9Z1FuUezSW2zdkuFH';
$testnetOperationalAccountAddress = 'rBfWDieu16DVYFKzS4FvCbCKXP9LCG2tVH';
$operationalWallet = Wallet::fromSeed($testnetStandbyAccountSeed);

$client = new JsonRpcClient("https://s.altnet.rippletest.net:51234");

$tx = [
    "TransactionType" => "Payment",
    "Account" => $testnetStandbyAccountAddress,
    "Amount" => xrpToDrops("100"),
    "Destination" => $testnetOperationalAccountAddress
];

$autofilledTx = $client->autofill($tx);

print_r($autofilledTx);