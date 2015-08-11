<?php

namespace Arya\Test;

use Amp\Artax\Client, Amp\Artax\Request;

class IntegrationTest extends \PHPUnit_Framework_TestCase {

    private static $client;
    private static $baseUri;

    public static function setupBeforeClass() {
        self::$client = new Client;
        self::$baseUri = sprintf('http://%s:%d', WEB_SERVER_HOST, WEB_SERVER_PORT);
    }

    public function testFunctionTarget() {
        $uri = self::$baseUri . '/test-function-target';
        $response = \Amp\wait(self::$client->request($uri));
        $this->assertEquals('test', $response->getBody());
    }

    public function testLambdaTarget() {
        $uri = self::$baseUri . '/test-lambda-target';
        $response = \Amp\wait(self::$client->request($uri));
        $this->assertEquals('test', $response->getBody());
    }

    public function testStaticTarget() {
        $uri = self::$baseUri . '/test-static-target';
        $response = \Amp\wait(self::$client->request($uri));
        $this->assertEquals('test', $response->getBody());
    }

    public function testInstanceMethodTarget() {
        $uri = self::$baseUri . '/test-instance-method-target';
        $response = \Amp\wait(self::$client->request($uri));
        $this->assertEquals('2 | 1', $response->getBody());
    }

    public function testRouteArgs() {
        $uri = self::$baseUri . '/arg1/arg2/42';
        $response = \Amp\wait(self::$client->request($uri));
        $this->assertEquals('arg1 | arg2 | 42 | arg1 | arg2 | 42', $response->getBody());
    }

    public function testRouteArgsUrlDecodedByDefault() {
        $uri = self::$baseUri . '/arg1/%3Ctest%3E/42';
        $response = \Amp\wait(self::$client->request($uri));
        $this->assertEquals('arg1 | <test> | 42 | arg1 | <test> | 42', $response->getBody());
    }

    public function test404OnUnmatchedNumericRouteArg() {
        $uri = self::$baseUri . '/arg1/arg2/should-be-numeric-but-isnt';
        $response = \Amp\wait(self::$client->request($uri));
        $this->assertEquals(404, $response->getStatus());
    }

    public function test404OnUnmatchedRoute() {
        $uri = self::$baseUri . '/some-route-that-clearly-doesnt-exist';
        $response = \Amp\wait(self::$client->request($uri));
        $this->assertEquals(404, $response->getStatus());
    }

    public function test500OnTargetOutput() {
        $uri = self::$baseUri . '/generates-output';
        $response = \Amp\wait(self::$client->request($uri));
        $this->assertEquals(500, $response->getStatus());
    }

    public function testComplexResponse() {
        $uri = self::$baseUri . '/complex-response';
        $response = \Amp\wait(self::$client->request($uri));
        $this->assertEquals(234, $response->getStatus());
        $this->assertEquals('Custom Reason', $response->getReason());
        $this->assertTrue($response->hasHeader('X-My-Header'));
        $myHeaders = $response->getHeader('X-My-Header');
        $this->assertEquals(2, count($myHeaders));
        list($header1, $header2) = $myHeaders;
        $this->assertEquals(1, $header1);
        $this->assertEquals(2, $header2);
        $this->assertEquals('zanzibar!', $response->getBody());
    }

    public function testInvalidQueryParameterType() {
        $uri = self::$baseUri . '/test-invalid-query-parameter-type?arg1[]=value';
        $response = \Amp\wait(self::$client->request($uri));
        $this->assertEquals(400, $response->getStatus());
        $this->assertEquals('Bad Input Parameter', $response->getReason());
    }

    public function testAppWideBeforeMiddleware() {
        $uri = self::$baseUri . '/test-function-target';
        $response = \Amp\wait(self::$client->request($uri));
        $this->assertTrue($response->hasHeader('X-Before-Test'));
        $this->assertEquals(42, current($response->getHeader('X-Before-Test')));
    }

    public function testAfterMiddlewareWithUriFilter() {
        // Matches /zanzibar/* URI filter
        $uri = self::$baseUri . '/zanzibar/test';
        $response = \Amp\wait(self::$client->request($uri));
        $this->assertTrue($response->hasHeader('X-Zanzibar'));
        $this->assertEquals('zanzibar!', current($response->getHeader('X-Zanzibar')));

        // URI doesn't match /zanzibar/* filter
        $uri = self::$baseUri . '/test-function-target';
        $response = \Amp\wait(self::$client->request($uri));
        $this->assertFalse($response->hasHeader('X-Zanzibar'));
    }

    public function testFatalRouteTarget() {
        $uri = self::$baseUri . '/fatal';
        $response = \Amp\wait(self::$client->request($uri));
        $this->assertEquals(500, $response->getStatus());
    }

    public function testExceptionRouteTarget() {
        $uri = self::$baseUri . '/fatal';
        $response = \Amp\wait(self::$client->request($uri));
        $this->assertEquals(500, $response->getStatus());
    }

    public function testMethodNotAllowedResponseOnUriMatchWithUnroutableMethod() {
        $uri = self::$baseUri . '/test-function-target';
        $request = new Request;
        $request->setUri($uri)->setMethod('POST');
        $response = \Amp\wait(self::$client->request($request));
        $this->assertEquals(405, $response->getStatus());
    }
}
