<?php

namespace Arya\Test;

use Arya\Response;

class ResponseTest extends \PHPUNIT_Framework_TestCase {

    private $response;

    public function setup() {
        $this->response = new Response;
    }

    public function testCanConstructWithArray() {
        $this->assertTrue(is_object(new Response(['X-FOO' => 'BAR'])));

    }

    public function testGetStatusDefaultsTo200() {
        $this->equalTo($this->response->getStatus(), 200);
    }

    /**
    * @expectedException \InvalidArgumentException
    */
    public function testSetStatusThrowsInvalidArgumentExceptionIfStatusCodeBelow100() {
        $this->response->setStatus(99);
    }

    /**
    * @expectedException \InvalidArgumentException
    */
        public function testSetStatusThrowsInvalidArgumentExceptionIfStatusCodeOver599() {
        $this->response->setStatus(600);
    }

    public function testSetStatusConvertsStringsToIntegers() {
        $this->response->setStatus('404');
        $this->assertInternalType('int', $this->response->getStatus());
    }

    public function testStatusSetterAndGetter() {
        $this->response->setStatus(404);
        $this->equalTo(404, $this->response->getStatus());
    }

    public function testSetStatusIsChainable() {
        $this->assertSame($this->response, $this->response->setStatus(503));
    }

    public function testGetReasonPhraseGetterAndGetter() {
        $this->response->setReasonPhrase('Found');
        $this->assertSame('Found', $this->response->getReasonPhrase());
    }

    /**
    * @expectedException \InvalidArgumentException
    */
    public function testSetReasonPhraseThrowsInvalidArgumentExceptionIfArgumentNotString() {
        $this->response->setReasonPhrase(404);
    }

    public function testSetReasonPhraseIsChainable() {
        $this->assertSame($this->response, $this->response->setReasonPhrase('Foo'));
    }

    public function testAllheadersSetterAndGetter() {
        $this->response->setAllHeaders(['FOO' =>'BAR', 'BAR' => 'BAZ']);
        $headers = $this->response->getAllHeaders();
        $this->assertSame($headers['FOO'][0], 'BAR');
        $this->assertSame($headers['BAR'][0], 'BAZ');
    }

    public function testGetAllHeaderIncludesCookiesInReturnValueIfSet() {
        $this->response->setCookie('Foo','bar');
        $this->assertSame(
            $this->response->getAllHeaders()['Set-Cookie'][0],
            'Foo=bar'
        );
    }

    /**
    * @expectedException PHPUnit_Framework_Error
    */
    public function testSetAllHeadersFirstArgumentMustBeArray() {
        $this->response->setAllHeaders();
    }

    public function testSetAllHeadersIsChainable() {
        $response = $this->response->setAllHeaders([]);
        $this->assertSame($this->response, $response);
    }

    public function testGetAllHeaderLinesReturnArrayOfFormattedStrings() {
        $this->response->setHeader('Foo', 'Bar');
        $this->response->setHeader('Bar', 'Baz');
        $headerLines = $this->response->getAllHeaderLines();
        $this->assertSame('Foo: Bar', $headerLines[0]);
        $this->assertSame('Bar: Baz', $headerLines[1]);
    }

    /**
    * @expectedException \InvalidArgumentException
    * @expectedExceptionMessage Non-empty string field name required at Argument 1
    */
    public function testSetHeaderThrowsInvalidArgumentExceptionIfFieldArgumentIsEmptyStringOrUnconvertable() {
        $this->response->setHeader('', 'Bar');
    }

    /**
    * @expectedException \InvalidArgumentException
    * @expectedExceptionMessage Invalid cookie string
    */
    public function testSetHeaderThrowsInvalidArgumentExceptionIfFieldArgumentIsSetCookieAndValueIEqualsFalse() {
        $this->response->setHeader('SET-COOKIE', 0);
    }

    public function testSetHeaderIsChainableIfFieldArgumentIsSetCookie() {
        $this->assertSame($this->response, $this->response->setHeader('SET-COOKIE', 'Foo=Bar'));
    }

    /**
    * @expectedException \InvalidArgumentException
    * @expectedExceptionMessage Invalid header; scalar or one-dimensional array of scalars required
    */
    public function testSetHeaderThrowsInvalidArgumentExceptionIfValueIsNotValidArray() {
        $this->response->setHeader('X-FOO', ['Scalable', new \StdClass]);
    }

    /**
    * @expectedException \DomainException
    * @expectedExceptionMessage Header field is not assigned: Foo
    */
    public function testGetHeaderThrowsDomainExceptionIfHeaderNotAssigned() {
        $this->response->getHeader('Foo');
    }

    public function testGetHeaderIsCaseInsensitive() {
        $this->response->setHeader('fOo', 'bar');
        $this->assertSame($this->response->getHeader('FoO'), 'bar');
    }

    public function testHeaderSetterAndGetter() {
        $this->response->setHeader('X-FOO', 'BAR');
        $this->assertSame($this->response->getHeader('X-FOO'), 'BAR');
    }

    public function testSetHeaderSetterValueArgumentTakesArrayOfScalars() {
        $scalars = ['Foo', 'Bar', 1, 2, 3];
        $this->response->setHeader('X-FOO', $scalars);
        $this->assertSame($scalars, $this->response->getHeader('X-FOO'));
    }

    public function testSetHeaderIsChainable() {
        $response = $this->response->setHeader('X-FOO', 'BAR');
        $this->assertSame($this->response, $response);
    }

    /**
    * @expectedException \DomainException
    * @expectedExceptionMessage Header line must match the format "Field-Name: value"
    */
    public function testSetHeaderLineThrowsDomainExceptionIfMissingSeperator() {
        $this->response->setHeaderLine('Invalid Foo');
    }

    public function testSetHeaderLineTrimsHeaderName() {
        $this->response->setHeaderLine(' name  :Foo');
        $reflection = new \ReflectionClass($this->response);
        $reflectionHeaders = $reflection->getProperty('headers');
        $reflectionHeaders->setAccessible(TRUE);
        $headers = $reflectionHeaders->getValue($this->response);
        $this->assertArrayHasKey('name', $headers);
    }

    public function testSetHeaderLineLeftTrimsHeaderValue() {
        $this->response->setHeaderLine(' Foo:   value ');
        $reflection = new \ReflectionClass($this->response);
        $reflectionHeaders = $reflection->getProperty('headers');
        $reflectionHeaders->setAccessible(TRUE);
        $headers = $reflectionHeaders->getValue($this->response);
        $this->assertSame('value ', $headers['Foo'][0]);
    }

    public function testSetHeaderLineSetter() {
        $this->response->setHeaderLine('Foo: Bar');
        $this->assertSame($this->response->getHeader('Foo'), 'Bar');
    }

    public function testSetHeaderLineIsChainable() {
        $response = $this->response->setHeaderLine('Foo: Bar');
        $this->assertSame($response, $this->response);
    }

    /**
    * @expectedException PHPUnit_Framework_Error
    */
    public function testSetAllHeaderLinesArgumentMustBeArray() {
        $this->response->setAllHeaderLines(1);
    }

    public function testAddAllHeaderLinesLeftTrimsFieldValue() {
        $this->response->addAllHeaderLines(["X-Foo: \t    Bar"]);
        $this->assertSame(['X-Foo: Bar'], $this->response->getAllHeaderLines());
    }

    /**
    * @expectedException \DomainException
    * @expectedExceptionMessage Invalid header field
    */
    public function testAddAllHeaderLinesThrowDomainExceptionIfFieldIsEmpty() {
        $this->response->addAllHeaderLines([" :Bar"]);
    }

    public function testSetAllHeaderLinesIsChainable() {
        $response = $this->response->setAllHeaderLines(['X-Foo: some value']);
        $this->assertSame($response, $this->response);
    }

    public function testAddHeaderSetsHeaderIfNameIsNotSet() {
        $this->response->addHeader('Foo', 'Bar');
        $this->assertSame('Bar', $this->response->getHeader('Foo'));
    }

    public function testAddHeaderAppendsToHeaderValue() {
        $this->response->setHeader('Foo', 'Bar');
        $this->response->addHeader('Foo', 'Baz');
    }

    public function testAddHeaderIsChainable() {
        $this->assertSame($this->response, $this->response->addHeader('Foo', 'Bar'));
    }

    public function testAddAllHeaderLines() {
        $this->response->addHeader('FOO', 'BAR');
        $this->response->addHeader('BAR', 'BAZ');
        $this->response->addAllHeaderLines(['FOO: BAZ', 'BAR: QUX']);
        $headers = $this->response->getAllHeaders();
        $this->assertSame('BAR', $headers['FOO'][0]);
        $this->assertSame('BAZ', $headers['FOO'][1]);
        $this->assertSame('BAZ', $headers['BAR'][0]);
        $this->assertSame('QUX', $headers['BAR'][1]);
    }

    public function testHasHeaderReturnFalseIfHeaderIsUnset() {
        $this->assertFalse($this->response->hasHeader('Foo'));
    }

    public function testHasHeaderReturnTrufIfHeaderIsUsed() {
        $this->response->setHeader('FOO', 'Bar');
        $this->response->setHeader('SET-COOKIE', 'Foo=bar');
        $this->assertTrue($this->response->hasHeader('SET-COOKIE'));
        $this->assertTrue($this->response->hasHeader('FOO'));
    }

    public function testHasHeaderIsCaseInsensitive() {
        $this->response->setHeader('FOO', 'Bar');
        $this->assertTrue($this->response->hasHeader('fOo'));
    }

    public function testRemoveHeaderRemovesHeader() {
        $this->response->addHeader('Foo', 'Bar');
        $this->response->removeHeader('Foo');
        $this->assertFalse($this->response->hasHeader('Foo'));
    }

    public function testRemoveHeaderPurgesCookiesIfSet() {
        $this->response->addHeader('SET-COOKIE', 'Foo=Bar');
        $this->response->removeHeader('SET-COOKIE');
        $this->assertFalse($this->response->hasHeader('SET-COOKIE'));
    }

    public function testRemoveHeaderIsCaseInsensitive() {
        $this->response->addHeader('Foo', 'Bar');
        $this->response->removeHeader('FOo');
        $this->assertFalse($this->response->hasHeader('Foo'));
    }

    public function testRemoveHeaderIsChainable() {
        $this->response->addHeader('Foo', 'Bar');
        $response = $this->response->removeHeader('Foo');
        $this->assertSame($this->response, $response);
    }

    public function testRemoveAllHeadersRemovesAllHeaders() {
        $this->response->addHeader('Foo', 'Bar');
        $this->response->addHeader('SET-COOKIE', 'Bar=Baz');
        $this->response->removeAllHeaders();
        $this->assertFalse($this->response->hasHeader('Foo'));
        $this->assertFalse($this->response->hasHeader('SET-COOKIE'));
    }

    public function testRemoveAllHeadersIsChainable() {
        $this->assertSame($this->response, $this->response->removeAllHeaders());
    }

    public function testSetCookieEncodesCookieValue() {
        $cookie = "foobaz=\"bar\"";
        $this->response->setCookie('Foo', $cookie);
        $this->assertSame(
            $this->response->getHeader('SET-COOKIE'),
            'Foo=foobaz%3D%22bar%22'
        );
    }

    public function testSetCookieOptionsArgument() {
        $this->response->setCookie(
            'Foo',
            'Bar',
            [
                'expire' => 42,
                'path' => 'path',
                'domain' => 'FooDomain.bar',
                'secure' => TRUE,
                'httponly' => TRUE
            ]
        );
        $this->assertSame(
            $this->response->getHeader('SET-COOKIE'),
            'Foo=Bar; Expires=42; Path=path; Domain=FooDomain.bar; Secure; HttpOnly'
        );
    }

    public function testSetCookieCanSetCookieWithoutValue() {
        $this->response->setCookie('Bar');
        $this->assertTrue($this->response->hasCookie('Bar'));
    }

    public function testCookieSetter() {
        $this->response->setCookie('Bar', 'Baz');
        $this->assertSame($this->response->getHeader('SET-COOKIE'), 'Bar=Baz');
    }

    public function testSetCookieIsChainable() {
        $this->assertSame($this->response, $this->response->setCookie('Foo', 'Bar'));
    }

    public function testSetRawCookieSetter() {
        $this->response->setRawCookie('Foo==', '/Baz');
        $this->assertSame('Foo===/Baz', $this->response->getHeader('SET-COOKIE'));
    }

    public function testSetRawCookieOptionsArgument() {
        $this->response->setRawCookie(
            'Foo==',
            '/Bar',
            [
                'expire' => 42,
                'path' => 'path',
                'domain' => 'FooDomain.bar',
                'secure' => TRUE,
                'httponly' => TRUE
            ]
        );
        $this->assertSame(
            $this->response->getHeader('SET-COOKIE'),
            'Foo===/Bar; Expires=42; Path=path; Domain=FooDomain.bar; Secure; HttpOnly'
        );
    }

    public function testSetRawCookieIsChainable() {
        $this->assertSame($this->response, $this->response->setRawCookie('Foo', 'Bar'));
    }

    public function testSetHasCookieReturnFalseIfCookieIsMissing() {
        $this->assertFalse($this->response->hasCookie('Foo'));
    }

    public function testSetHasCookieReturnTrueIfCookieIsSet() {
        $this->response->setCookie('Foo');
        $this->assertTrue($this->response->hasCookie('Foo'));
    }

    public function testRemoveCookieRemovesCookie() {
        $this->response->setCookie('Foo');
        $this->response->removeCookie('Foo');
        $this->assertFalse($this->response->hasCookie('Foo'));
    }

    public function testRemoveCookieIsChainable() {
        $this->assertSame($this->response, $this->response->removeCookie('Foo'));
    }

    public function testSetHeaderIsCaseInsensitive()
    {
        $this->response->setHeader('X-heLlo', 'Foo');
        $this->assertSame('Foo', $this->response->getHeader('x-HElLo'));
    }

    /**
    * @expectedException \InvalidArgumentException
    */
    public function testSetHeaderThrowsInvalidArgumentExceptionIfFieldArgumentIsSetCookieAndValueIsMissingEqualOperator() {
        $this->response->setHeader('SET-COOKIE', 'missingEqualOperator');
    }

    public function testSetHeaderSetterCookieOptionsSecureAndHttpOnly() {
        $cookie = 'foo=bar;httponly=true;secure=true';
        $this->response->setHeader('SET-COOKIE', $cookie);
        $this->assertSame('foo=bar; Secure; HttpOnly', $this->response->getHeader('SET-COOKIE'));
    }

    /**
    * @expectedException \InvalidArgumentException
    * @expectedExceptionMessage Invalid cookie string: Foo
    */
    public function testSetHeaderSetCookieThrowsInvalidArgumentExceptionIfPartNotSecureOrHttpOnlyAndMissingEqualOperator() {
        $cookie = 'foo=bar;Foo';
        $this->response->setHeader('SET-COOKIE', $cookie);
    }

    public function testSetHeaderSetCookieTrimRightHandValues() {
        $cookie = 'Foo=Bar; Path=Junk';
        $junk = "\t\n\0\x0B\x20";
        $this->response->setHeader('SET-COOKIE', $cookie . $junk);
        $header = $this->response->getHeader('SET-COOKIE');
        $this->assertSame($cookie, $header);
    }

    public function testGetBodyGetter() {
        $this->response->setBody('Foo');
        $this->assertSame('Foo', $this->response->getBody());
    }

    public function testHasBodyReturnTrueIfAssigned() {
        $this->response->setBody('Foo');
        $this->assertTrue($this->response->hasBody('Foo'));
    }

    public function testHasBodyReturnFalseIfNotAssigned() {
        $this->assertFalse($this->response->hasBody('Foo'));
    }

    /**
    * @expectedException \InvalidArgumentException
    */
    public function testSetBodyThrowsInvalidArgumentExceptionIfArgumentNotStringCallableOrNull() {
        $this->response->setBody(new \StdClass);
    }

    public function testSetBodyTakesACallableAsArgument() {
        $callable = function() { return 'Foo'; };
        $this->response->setBody($callable);
        $this->assertTrue(is_callable($this->response->getBody()));
    }

    public function testSetBodyAssignsHeadersFromBodyIfArgumentIsInstanceOfBody() {
        $stubBody= $this->getMock('Arya\Body');
        $stubBody->expects($this->any())
             ->method('getHeaders')
             ->will($this->returnValue(['Foo' => ['Bar', 'Baz']]));
        $this->response->setBody($stubBody);
        $fooHeader = $this->response->getHeader('Foo');
        $this->assertSame($fooHeader[0], 'Bar');
        $this->assertSame($fooHeader[1], 'Baz');
    }

    public function testGetBodyReturnsNullIfNotBodyIsSet() {
        $this->assertInternalType('null', $this->response->getBody());
    }

    public function testToArrayReturnsArrayWithAllContent() {
        $response = new Response(['asgiKey' => 'asgiValue']);
        $response->setHeader('Foo', 'Bar');
        $response->setStatus(201);
        $response->setReasonPhrase('Foo Found');
        $response->setBody('Bar');
        $response->setCookie('Bar', 'Baz');
        $content = $response->toArray();
        $this->assertSame($content['status'], 201);
        $this->assertSame($content['reason'], 'Foo Found');
        $this->assertSame($content['headers'][0], 'Foo: Bar');
        $this->assertSame($content['headers'][1], 'Set-Cookie: Bar=Baz');
        $this->assertSame($content['body'], 'Bar');
        $this->assertSame($content['asgiKey'], 'asgiValue');
    }

    public function testClearGoesBackToDefault() {
        $defaultProperties = $this->getProperties($this->response);
        $response = new Response(['asgiKey' => 'asgiValue']);
        $response->setHeader('Foo', 'Bar');
        $response->setStatus(201);
        $response->setReasonPhrase('Foo Found');
        $response->setBody('Bar');
        $response->setCookie('Bar', 'Baz');
        $response->clear();
        $propertiesAfterClear = $this->getProperties($response);
        $this->assertEquals($defaultProperties, $propertiesAfterClear);
    }

    public function testImportArrayClearsValueBeforeImporting() {
        $defaultReason = $this->response->getReasonPhrase();
        $this->response->setHeader('Foo', 'Bar');
        $this->response->setStatus(201);
        $this->response->setReasonPhrase('Foo Found');
        $this->response->importArray(['status' => 404]);
        $this->assertSame($defaultReason, $this->response->getReasonPhrase());
    }

    public function testImportArrayIsChainable() {
        $this->assertSame($this->response->importArray([]), $this->response);
    }

    public function testImportTransfersResponseContent() {
        $response = new Response(['asgiKey' => 'asgiValue']);
        $response->setHeader('Foo', 'Bar');
        $response->setStatus(201);
        $response->setReasonPhrase('Foo Found');
        $response->setCookie('Bar', 'Baz');
        $properties = $this->getProperties($response);
        $defaultProperties = $this->getProperties($this->response);
        $this->response->import($response);
        $this->assertSame('Bar', $this->response->getHeader('Foo'));
        $this->assertSame(201, $this->response->getStatus());
        $this->assertSame('Foo Found', $this->response->getReasonPhrase());
        $this->assertSame('Bar=Baz', $this->response->getHeader('SET-COOKIE'));
        $this->assertSame('asgiValue', $this->response['asgiKey']);
    }

    private function getProperties() {
        $getProperties = function($object) {
            $responseReflection = new \ReflectionClass($this->response);
            foreach($responseReflection->getProperties() as $property) {
                $property->setAccessible(TRUE);
                $properties[$property->getName()] = $property->getValue($this->response);
            }

            return $properties;
        };
    }

    /**
    * @expectedException \DomainException
    */
    public function testArrayAccessOffsetGetThrowsDomainExceptionIfInvalidOffset() {
        $this->response['Foo'];
    }

    public function testArrayAccessOffsetStatusGetter() {
        $this->response->setStatus(404);
        $this->assertSame(404, $this->response['status']);
    }

    public function testArrayAccessOffsetReasonGetter() {
        $this->response->setReasonPhrase('Foo');
        $this->assertSame('Foo', $this->response['reason']);
    }

    public function testArrayAccessOffsetHeadersGetterGetsHeaderLinesIfHeaderIsEmpty() {
        $this->response->setHeaderLine('Foo: bar');
        $this->response->setHeaderLine('Bar: baz');
        $this->assertSame(['Foo: bar', 'Bar: baz'], $this->response['headers']);
    }

    public function testArrayAccessOffsetHeadersGetter() {
        $this->response->setHeader('Foo', 'bar');
        $this->assertSame(['Foo: bar'], $this->response['headers']);
    }

    public function testArrayAccessOffsetBodyGetter() {
        $this->response->setBody('Foo');
        $this->assertSame('Foo', $this->response['body']);
    }

    public function testArrayAccessOffsetAsgiMapOffsetGetter() {
        $response = new Response(['x' => 'y']);
        $this->assertSame('y', $response['x']);
    }

    public function testArrayAccessOffsetSetStatus() {
        $this->response['status'] = 404;
        $this->assertSame(404, $this->response->getStatus());
    }

    public function testArrayAccessOffsetSetReason() {
        $this->response['reason'] = 'NOT FOUND';
        $this->assertSame('NOT FOUND', $this->response->getReasonPhrase());
    }

    public function testArrayAccessOffsetSetBody() {
        $this->response['body'] = 'Foo';
        $this->assertSame('Foo', $this->response->getBody());
    }

    public function testArrayAccessOffsetSetAllHeaderLines() {
        $this->response['headers'] = ['Foo: Bar', 'Bar: Baz'];
        $this->assertSame(['Foo' => ['Bar'], 'Bar' => ['Baz']], $this->response->getAllHeaders());
    }

    public function testArrayAccessOffsetSetAsgi() {
        $this->response['asgiKey'] = 'asgiValue';
        $this->assertSame('asgiValue', $this->response['asgiKey']);
    }

    public function  testArrayAccessOffsetExistsReturnFalseIfArgumentNotAssigned() {
        $this->assertFalse($this->response->offsetExists('foo'));
    }

    public function testArrayAccessOffsetExistsReturnTrueIfArgumentIsAssigned() {
        $response = new Response(['asgiKey' => 'asgiValue']);
        $response->setHeader('Foo', 'Bar');
        $response->setStatus(201);
        $response->setReasonPhrase('Foo Found');
        $response->setBody('Bar');
        $this->assertTrue($response->offsetExists('asgiKey'));
        $this->assertTrue($response->offsetExists('headers'));
        $this->assertTrue($response->offsetExists('status'));
        $this->assertTrue($response->offsetExists('reason'));
        $this->assertTrue($response->offsetExists('body'));
    }

    public function testArrayAccessOffsetUnsetUnsetsBody() {
        $this->response->setBody('Foo');
        unset($this->response['body']);
        $this->assertInternalType('null', $this->response->getBody());
    }

    public function testArrayAccessOffsetUnsetDefaultsStatusCodeTo200() {
        $this->response->setStatus(404);
        unset($this->response['status']);
        $this->assertSame(200, $this->response->getStatus());
    }

    public function testArrayAccessOffsetUnsetDefaultsReasonToEmptyString() {
        $this->response->setReasonPhrase('Foo');
        unset($this->response['reason']);
        $this->assertSame('', $this->response->getReasonPhrase());
    }

    public function testArrayAccessOffsetUnsetDefaultsHeadersToEmptyArray() {
        $this->response->setHeader('Foo', 'bar');
        unset($this->response['headers']);
        $this->assertSame([], $this->response->getAllHeaders());
    }

    public function testArrayAccessOffsetUnsetAsgiKey() {
        $response = new Response(['x' => 'y']);
        $this->assertTrue($response->offsetExists('x'));
        unset($response['x']);
        $this->assertFalse($response->offsetExists('x'));
    }

}
