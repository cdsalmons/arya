<?php

namespace Arya\Test;

use Arya\JsonBody;

class JsonBodyTest extends \PHPUnit_Framework_TestCase {

    public function setup() {
        $this->jsonBody = new JsonBody(['foo' => 'bar']);
    }

    /**
    * @requires PHP 5.5
    */
    public function testFlagsArgumentIsInEffect() {
        $jsonBody = new JsonBody(['foo' => '<p>bar</p>'], \JSON_HEX_TAG);
        $this->assertJsonStringEqualsJsonString(
            '{"foo":"\u003Cp\u003Ebar\u003C\/p\u003E"}',
            $this->getJsonBodyOutput($jsonBody)
        );
    }

    /**
    * @expectedException \RuntimeException
    * @expectedExceptionMessage depth parameter not available before PHP 5.5
    */
    public function testThrowExceptionIfDepthParamIsNotAvailable() {
        if (PHP_VERSION_ID >= 50500) {
            $this->markTestSkipped(
              'depth is available since PHP 5.5'
            );
        }

        $this->jsonBody = new JsonBody(['foo' => 'bar'], \JSON_HEX_TAG, 1024);
    }

    /**
    * @requires PHP 5.5
    * @expectedException \RuntimeException
    * @expectedExceptionMessage Maximum stack depth exceeded
    */
    public function testThrowsRunTimeExceptionIfDepthArgumentLowerThenStackDepth() {
        new JsonBody(['foo' => ['bar' => ['<p>baz</p>']]], 0, 1);
    }

    public function testIsInvokeable() {
        $json = $this->getJsonBodyOutput($this->jsonBody);
        $this->assertSame('{"foo":"bar"}',$json);
    }

    private function getJsonBodyOutput(JsonBody $jsonBody) {
        ob_start();
        $jsonBody();
        $json = ob_get_clean();
        return $json;
    }

    public function testGetHeadersReturnCorrectContentLength() {
        $data = ['foo' => ['bar' => ['baz' => ['quux']]]];
        $encodedLength = strlen(json_encode($data));
        $jsonBody = new JsonBody($data);
        $this->assertSame($encodedLength, $jsonBody->getHeaders()['Content-Length']);
    }

    public function testGetHeadersReturnContentTypeJsonUtf8() {
        $this->assertSame(
            'application/json; charset=utf-8',
            $this->jsonBody->getHeaders()['Content-Type']
        );
    }
}
