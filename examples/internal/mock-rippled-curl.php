<?php

use donatj\MockWebServer\MockWebServer;

require __DIR__ . '/../../vendor/autoload.php';

$server = new MockWebServer;
$server->start();

$url = $server->getServerRoot() . '/endpoint?get=foobar';

echo "Requesting: $url\n\n";
echo file_get_contents($url);

$ch = curl_init();

// Return Page contents.
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// Grab URL and pass it to the variable
curl_setopt($ch, CURLOPT_URL, $url);

$result = curl_exec($ch);

echo $result;