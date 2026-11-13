<?php declare(strict_types=1);

/**
 * A node that never gives a complete answer, for JsonRpcClientTest.
 *
 *   php broken-node.php <port> drop   accepts, reads the request, closes without a byte
 *   php broken-node.php <port> cut    sends status 200 and the start of a JSON body, then stalls
 *
 * Serves one connection after another until the test stops the process. A
 * connection that sends nothing (the test's readiness probe) is closed at once.
 */

/** @var list<string> $argv */
$port = $argv[1] ?? '';
$mode = $argv[2] ?? '';
$server = stream_socket_server("tcp://127.0.0.1:{$port}", $errno, $errstr);
if ($server === false) {
    fwrite(STDERR, "cannot listen on {$port}: {$errstr}\n");
    exit(1);
}

while (true) {
    $connection = @stream_socket_accept($server, -1);
    if ($connection === false) {
        continue;
    }
    if ((string) fread($connection, 65536) === '') {
        fclose($connection);
        continue;
    }
    if ($mode === 'cut') {
        fwrite($connection, "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\nContent-Length: 4096\r\n\r\n{\"result\":{\"info\":");
        fflush($connection);
        sleep(10);
    }
    fclose($connection);
}
