<?php

namespace XRPL_PHP\Test\MockRippled;

use donatj\MockWebServer\Response;

class MockRippledResponse extends Response {

    private string $requestJson;

    public function __construct(
        array|string $requestJson,
        array|string $responseJson
    )
    {
        if(is_array($requestJson)) {
            $requestJson = json_encode($requestJson);
        }

        $this->requestJson = $requestJson;

        if(is_array($responseJson)) {
            $responseJson = json_encode($responseJson);
        }

        parent::__construct($responseJson);
    }

    /**
     * @inheritdoc
     */
    public function getRef(): string {
        $content = json_encode([
            $this->requestJson,
            200,
            [],
        ]);

        return md5($content);
    }
}