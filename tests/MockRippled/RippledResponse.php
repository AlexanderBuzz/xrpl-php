<?php

namespace XRPL_PHP\Test\MockRippled;

use donatj\MockWebServer\MockWebServer;
use donatj\MockWebServer\RequestInfo;
use donatj\MockWebServer\ResponseInterface;

/**
 *
 */
class RippledResponse implements ResponseInterface {

    private string $method;

    private array $payload;

    public function __construct(string $method, array $payload)
    {
        $this->method = $method;
        $this->payload = $payload;

        //parent::__construct($payload);
    }

    public function getRef() {
        return md5(MockWebServer::VND . '.default-ref');
    }

    public function getBody( RequestInfo $request ) {
        //$requestPost = $request->getPost();
        //$responsePost = is_array($this->payload) ? json_encode($this->payload) : $this->payload;

        return json_encode($request, JSON_PRETTY_PRINT) . "\n";
    }

    public function getHeaders( RequestInfo $request ) {
        return [ 'Content-Type' => 'application/json' ];
    }

    public function getStatus( RequestInfo $request ) {
        return 200;
    }
}