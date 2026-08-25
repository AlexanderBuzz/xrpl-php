<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\MockRippled;

use donatj\MockWebServer\RequestInfo;
use donatj\MockWebServer\ResponseInterface;

/**
 * Serves canned rippled responses keyed by JSON-RPC method.
 *
 * MockRippledResponse routes by URL path, which only works when the test
 * controls the path it posts to. The client posts every JSON-RPC call to the
 * same root path, so anything that talks to rippled through the normal code
 * path - autofill(), submit(), the fee calculation - could not be tested
 * against it. This one reads the `method` out of the request body instead.
 *
 * Register it as the server's default response, then map methods to payloads:
 *
 *     $server->setDefaultResponse(new RpcMethodResponse([
 *         'fee' => ['drops' => ['open_ledger_fee' => '10']],
 *         'account_info' => ['account_data' => ['Sequence' => 23]],
 *     ]));
 *
 * The payloads are the contents of rippled's `result` object; the envelope is
 * added here.
 */
class RpcMethodResponse implements ResponseInterface
{
    /**
     * @param array<string, array> $resultsByMethod
     * @param array<string, mixed> $notFoundResult Returned for unmapped methods
     */
    public function __construct(
        private readonly array $resultsByMethod,
        private readonly array $notFoundResult = ['error' => 'unknownCmd']
    ) {
    }

    /**
     * Which methods this response knows about, for assertions in tests.
     *
     * @return string[]
     */
    public function getKnownMethods(): array
    {
        return array_keys($this->resultsByMethod);
    }

    public function getRef(): string
    {
        return md5(json_encode($this->resultsByMethod) ?: '');
    }

    public function getBody(RequestInfo $request): string
    {
        $method = self::readMethod($request);
        $result = $this->resultsByMethod[$method] ?? $this->notFoundResult;

        // rippled echoes the request's method back and wraps everything in
        // a result object; several code paths read `status` and `validated`.
        return json_encode([
            'result' => $result + ['status' => 'success'],
        ]) ?: '';
    }

    public function getHeaders(RequestInfo $request): array
    {
        return ['Content-Type' => 'application/json'];
    }

    public function getStatus(RequestInfo $request): int
    {
        return 200;
    }

    /**
     * The JSON-RPC method of a request, or an empty string if the body is not
     * a JSON-RPC call.
     */
    public static function readMethod(RequestInfo $request): string
    {
        $body = json_decode($request->getInput(), true);

        return is_array($body) && isset($body['method']) && is_string($body['method'])
            ? $body['method']
            : '';
    }
}
