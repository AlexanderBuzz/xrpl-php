<?php

namespace XRPL_PHP\Test\MockRippled;

use donatj\MockWebServer\MockWebServer;
use donatj\MockWebServer\Response;

class RippledServer extends MockWebServer
{
    public function __construct($port = 0, $host = '127.0.0.1')
    {
        $this->setRippledResponses();

        parent::__construct($port, $host);
    }

    private function setRippledResponses()
    {

    }
}