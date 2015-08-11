<?php

namespace Arya\Test;

use Arya\Sessions\Session;

class SessionTest extends \PHPUnit_Framework_TestCase {

    public function testConstructorDefaultsSecondsArgumentsIfNullToInstanceOfFileSessionHandler() {
        $session = $this->getBlackHoleSession();
        $reflection = new \ReflectionClass($session);
        $reflectionSessionHandler = $reflection->getProperty('handler');
        $reflectionSessionHandler->setAccessible(TRUE);
        $sessionHandler = $reflectionSessionHandler->getValue($session);
        $this->assertInstanceOf('\Arya\Sessions\FileSessionHandler', $sessionHandler);
    }

    /**
    * @expectedException \DomainException
    */
    public function testGetOptionThrowsDomainExceptionIfRequstedOptionIsNotSet() {
        $session = $this->getBlackHoleSession();
        $session->getOption('foo');
    }

    public function testGetOptionReturnsValidOption() {
        $session = $this->getBlackHoleSession();
        $this->assertSame(
            'ASESSID', // default session cookie name
            $session->getOption('cookie_name')
        );
    }

    public function testGetOptionIsCaseInsensitive() {
        $session = $this->getBlackHoleSession();
        $this->assertSame(
            'ASESSID', // default session cookie name
            $session->getOption('COOkie_NaME')
        );
    }

    /**
    * @expectedException \DomainException
    */
    public function testSetOptionThrowsDomainExceptionIfUnknownOption() {
        $session = $this->getBlackHoleSession();
        $session->setOption('Foo', 'bar');
    }

    /**
    * @expectedException \InvalidArgumentException
    * @expectedExceptionMessage Session cookie name expects a string: array specified
    */
    public function testSetOptionSetCookieNameThrowsInvalidArgumentExceptionIfValueNotString() {
        $session = $this->getBlackHoleSession();
        $session->SetOption('cookie_name', [1,2,3]);
    }

    /**
    * @expectedException \InvalidArgumentException
    * @expectedExceptionMessage Session cookie name must be at least 8 bytes in size
    */
    public function testSetOptionSetCookieNameThrowsInvalidArgumentExceptionIfArgumentValueIsLessThen5Chars() {
        $session = $this->getBlackHoleSession();
        $session->setOption('cookie_name', 'foo');
    }

    /**
    * @expectedException \InvalidArgumentException
    * @expectedExceptionMessage Non-alphanumeric character in session cookie name at index 3: @
    */
    public function testSetOptionSetCookieThrowsInvalidArgumentExceptionIfValueHasNonalphanumeric() {
        $session = $this->getBlackHoleSession();
        $session->setOption('cookie_name', 'foo@bar');
    }

    public function testSetOptionCanSetCookieName() {
        $session = $this->getBlackHoleSession();
        $cookieName = 'fooBarBaz';
        $session->setOption('cookie_name', $cookieName);
        $this->assertSame($cookieName, $session->getOption('cookie_name'));
    }

    /**
    * @expectedException \InvalidArgumentException
    */
    public function testSetOptionSetCookieDomainThrowsInvalidArgumentExceptionIfValueNotString() {
        $session = $this->getBlackHoleSession();
        $session->setOption('cookie_domain', [1,2,3]);
    }

    public function testSetOptionCanCookieDomain() {
        $session = $this->getBlackHoleSession();
        $cookieDomain = 'foo.com';
        $session->setOption('cookie_domain', $cookieDomain);
        $this->assertSame($cookieDomain, $session->getOption('cookie_domain'));
    }

    /**
    * @expectedException \InvalidArgumentException
    */
    public function testSetOptionSetCookiePathThrowsInvalidArgumentExceptionIfValueNotString() {
        $session = $this->getBlackHoleSession();
        $session->setOption('cookie_path', [1,2,3]);
    }

    public function testSetOptionCanSetCookiePath() {
        $session = $this->getBlackHoleSession();
        $cookiePath = '/';
        $session->setOption('cookie_path', $cookiePath);
        $this->assertSame($cookiePath, $session->getOption('cookie_path'));
    }

    public function testSetOptionSetCookieSecureFlagCastsValueToBool() {
        $session = $this->getBlackHoleSession();
        $session->setOption('cookie_secure', 1);
        $this->assertTrue($session->getOption('cookie_secure'));
    }

    public function testSetOptionCanSetCookieSecureFlag() {
        $session = $this->getBlackHoleSession();
        $session->setOption('cookie_secure', False);
        $this->assertFalse($session->getOption('cookie_secure'));
    }

    public function testSetOptionSetHttpOnlyFlagCastsValueToBool() {
        $session = $this->getBlackHoleSession();
        $session->setOption('cookie_httponly', 1);
        $this->assertTrue($session->getOption('cookie_httponly'));
    }

    public function testSetOptionCanSetHttpOnlyFlag() {
        $session = $this->getBlackHoleSession();
        $session->setOption('cookie_httponly', False);
        $this->assertFalse($session->getOption('cookie_httponly'));
    }

    /**
    * @expectedException \InvalidArgumentException
    */
    public function testSetOptionSetRefererCheckThrowsInvalidArgumentExceptionIfValueIsNotString() {
        $session = $this->getBlackHoleSession();
        $session->setOption('referer_check', [1,2,3]);
    }

    public function testSetOptionCanSetRefererCheck() {
        $session = $this->getBlackHoleSession();
        $refererCheck = 'foo';
        $session->setOption('referer_check', $refererCheck);
        $this->assertSame($refererCheck, $session->getOption('referer_check'));
    }

    public function testSetOptionCanSetSha1HashFunction() {
        $session = $this->getBlackHoleSession();
        $hashOption = 'sha1';
        $session->setOption('hash_function', $hashOption);
        $this->assertSame($hashOption, $session->getOption('hash_function'));
    }

    public function testSetOptionCanSetMd5HashFunction() {
        $session = $this->getBlackHoleSession();
        $hashOption = 'md5';
        $session->setOption('hash_function', $hashOption);
        $this->assertSame($hashOption, $session->getOption('hash_function'));
    }

    public function testSetOptionSetHashFunctionValueLowercasesValue() {
        $session = $this->getBlackHoleSession();
        $session->setOption('hash_function', 'MD5');
        $this->assertSame('md5', $session->getOption('hash_function'));
    }

    /**
    * @expectedException \DomainException
    */
    public function testSetOptionSetHashFunctionThrowsExceptionIfUnknownHashingAlgo() {
        $session = $this->getBlackHoleSession();
        $session->setOption('hash_function', 'FooBarBazQuux');
    }

    /**
    * @expectedException \DomainException
    */
    public function testSetOptionSetCacheLimiterThrowsExceptionIfUnknownLimiterValue() {
        $session = $this->getBlackHoleSession();
        $session->setOption('cache_limiter', 'UNKNOWN_FOO');
    }

    public function testSetOptionCanSetCacheLimiterWithNoCache() {
        $session = $this->getBlackHoleSession();
        $cacheLimiter = \Arya\Sessions\Session::CACHE_NOCACHE;
        $session->setOption('cache_limiter', $cacheLimiter);
        $this->assertSame($cacheLimiter, $session->getOption('cache_limiter'));
    }

    public function testSetOptionCanSetCacheLimiterWithPrivate() {
        $session = $this->getBlackHoleSession();
        $cacheLimiter = \Arya\Sessions\Session::CACHE_PRIVATE;
        $session->setOption('cache_limiter', $cacheLimiter);
        $this->assertSame($cacheLimiter, $session->getOption('cache_limiter'));
    }

    public function testSetOptionCanSetCacheLimiterWithPrivNoExp() {
        $session = $this->getBlackHoleSession();
        $cacheLimiter = \Arya\Sessions\Session::CACHE_PRIV_NO_EXP;
        $session->setOption('cache_limiter', $cacheLimiter);
        $this->assertSame($cacheLimiter, $session->getOption('cache_limiter'));
    }

    public function testSetOptionCanSetCacheLimiterWithPublic() {
        $session = $this->getBlackHoleSession();
        $cacheLimiter = \Arya\Sessions\Session::CACHE_PUBLIC;
        $session->setOption('cache_limiter', $cacheLimiter);
        $this->assertSame($cacheLimiter, $session->getOption('cache_limiter'));
    }

    public function testSetOptionSetCacheExpireDefaultTo180IfvalueFalseOrNullorZero() {
        $session = $this->getBlackHoleSession();
        $session->setOption('cache_expire', Null);
        $this->assertSame(180, $session->getOption('cache_expire'));
        $session->setOption('cache_expire', False);
        $this->assertSame(180, $session->getOption('cache_expire'));
        $session->setOption('cache_expire', 0);
        $this->assertSame(180, $session->getOption('cache_expire'));
    }

    public function testSetOptionSetCacheExpireFormatsToInteger() {
        $session = $this->getBlackHoleSession();
        $session->setOption('cache_expire', '123');
        $this->assertSame(123, $session->getOption('cache_expire'));
    }

    public function testSetOptionCanSetCacheExpire() {
        $session = $this->getBlackHoleSession();
        $cacheExpire = 120;
        $session->setOption('cache_expire', $cacheExpire);
        $this->assertSame($cacheExpire, $session->getOption('cache_expire'));
    }

    public function testSetOptionSetGarbageCollectionProbabillityDefaultTo1IfvalueFalseOrNullorZero() {
        $session = $this->getBlackHoleSession();
        $session->setOption('gc_probability', Null);
        $this->assertSame(1, $session->getOption('gc_probability'));
        $session->setOption('gc_probability', False);
        $this->assertSame(1, $session->getOption('gc_probability'));
        $session->setOption('gc_probability', 0);
        $this->assertSame(1, $session->getOption('gc_probability'));
    }

        public function testSetOptionSetGarbageCollectionProbabillityFormatsToInteger() {
            $session = $this->getBlackHoleSession();
        $session->setOption('gc_probability', '123');
        $this->assertSame(123, $session->getOption('gc_probability'));
    }

    public function testSetOptionCanSetGarbageCollectionProbability() {
        $session = $this->getBlackHoleSession();
        $gcProbability = 2;
        $session->setOption('gc_probability', $gcProbability);
        $this->assertSame($gcProbability, $session->getOption('gc_probability'));
    }

    public function testSetOptionSetGarbageCollectionDivisorDefaultTo100IfvalueFalseOrNullorZero() {
        $session = $this->getBlackHoleSession();
        $session->setOption('gc_divisor', Null);
        $this->assertSame(100, $session->getOption('gc_divisor'));
        $session->setOption('gc_divisor', False);
        $this->assertSame(100, $session->getOption('gc_divisor'));
        $session->setOption('gc_divisor', 0);
        $this->assertSame(100, $session->getOption('gc_divisor'));
    }

    public function testSetOptionSetGarbageCollectionDivisorFormatsToInteger() {
        $session = $this->getBlackHoleSession();
        $session->setOption('gc_divisor', '123');
        $this->assertSame(123, $session->getOption('gc_divisor'));
    }

    public function testSetOptionCanSetGarbageCollectionDivisor() {
        $session = $this->getBlackHoleSession();
        $gcProbability = 42;
        $session->setOption('gc_divisor', $gcProbability);
        $this->assertSame($gcProbability, $session->getOption('gc_divisor'));
    }

    public function testSetOptionSetGarbageMaxLifeTimeDefaultTo1440IfvalueFalseOrNullorZero() {
        $session = $this->getBlackHoleSession();
        $session->setOption('gc_maxlifetime', Null);
        $this->assertSame(1440, $session->getOption('gc_maxlifetime'));
        $session->setOption('gc_maxlifetime', False);
        $this->assertSame(1440, $session->getOption('gc_maxlifetime'));
        $session->setOption('gc_maxlifetime', 0);
        $this->assertSame(1440, $session->getOption('gc_maxlifetime'));
    }

    public function testSetOptionSetGarbageMaxLifeTimeFormatsToInteger() {
        $session = $this->getBlackHoleSession();
        $session->setOption('gc_maxlifetime', '123');
        $this->assertSame(123, $session->getOption('gc_maxlifetime'));
    }

    public function testSetOptionCanSetGarbageMaxLifeTime() {
        $session = $this->getBlackHoleSession();
        $gcProbability = 42;
        $session->setOption('gc_maxlifetime', $gcProbability);
        $this->assertSame($gcProbability, $session->getOption('gc_maxlifetime'));
    }

    public function testSetOptionSetStrictFlagCastsValueToBool() {
        $session = $this->getBlackHoleSession();
        $session->setOption('strict', 1);
        $this->assertTrue($session->getOption('strict'));
    }

    public function testSetOptionCanSetStrictFlag() {
        $session = $this->getBlackHoleSession();
        $session->setOption('strict', False);
        $this->assertFalse($session->getOption('strict'));
    }

    public function testSetOptionIsCaseInsensitive() {
        $session = $this->getBlackHoleSession();
        $session->setOption('STricT', False);
        $this->assertFalse($session->getOption('strict'));
    }

    public function testSetAllOptionsTakesArrayOfOptions() {
        $session = $this->getBlackHoleSession();
        $options = [
            'cookie_name' => 'FooBarBaz',
            'cookie_domain' => 'Bar.com',
            'cookie_path' => '/',
            'cookie_secure' => TRUE,
            'cookie_httponly' => FALSE,
            'referer_check' => 'Baz',
            'hash_function' => 'sha1',
            'cache_limiter' => 'public', // Session::CACHE_PUBLIC
            'cache_expire' => 240,
            'gc_probability' => 2,
            'gc_divisor' => 420,
            'gc_maxlifetime' => 608,
            'strict' => FALSE
        ];
        $session->setAllOptions($options);
        $this->assertSame('FooBarBaz', $session->getOption('cookie_name'));
        $this->assertSame('Bar.com', $session->getOption('cookie_domain'));
        $this->assertSame('/', $session->getOption('cookie_path'));
        $this->assertTrue($session->getOption('cookie_secure'));
        $this->assertFalse($session->getOption('cookie_httponly'));
        $this->assertSame('Baz', $session->getOption('referer_check'));
        $this->assertSame('sha1', $session->getOption('hash_function'));
        $this->assertSame('public', $session->getOption('cache_limiter'));
        $this->assertSame(240, $session->getOption('cache_expire'));
        $this->assertSame(2, $session->getOption('gc_probability'));
        $this->assertSame(420, $session->getOption('gc_divisor'));
        $this->assertSame(608, $session->getOption('gc_maxlifetime'));
        $this->assertFalse($session->getOption('strict'));
    }

    /**
    * @expectedException \Arya\Sessions\SessionException
    * @expectedExceptionMessage Failed opening session
    */
    public function testOpenThrowsExceptionIfUnableToOpenSession() {
        $requestMock = $this->getRequestMock();
        $fileSessionHandlerMock = $this->getFileSessionHandlerMock();
        $fileSessionHandlerMock->expects($this->any())->method('open')->will($this->returnValue(False));
        $fileSessionHandlerMock->expects($this->any())->method('close')->will($this->returnValue(True));
        $fileSessionHandlerMock->expects($this->any())->method('save')->will($this->returnValue(True));
        $session = new Session($requestMock, $fileSessionHandlerMock);
        $session->open();
    }

    public function testOpenImportsSessionIdAndDataFromRequestCookie() {
        $requestSessionId = 42;
        $requestMock = $this->getRequestMock();
        $requestMock->expects($this->any())->method('hasCookie')->will($this->returnValue(True));
        $requestMock->expects($this->any())->method('getCookie')->will($this->returnValue($requestSessionId));
        $fileSessionHandlerMock = $this->getFileSessionHandlerMock();
        $fileSessionHandlerMock->expects($this->any())->method('exists')->will($this->returnValue(True));
        $fileSessionHandlerMock->expects($this->any())->method('open')->will($this->returnValue(True));
        $fileSessionHandlerMock->expects($this->any())->method('read')->will($this->returnValue(['foo'=>'bar']));
        $fileSessionHandlerMock->expects($this->any())->method('save')->will($this->returnValue(True));
        $fileSessionHandlerMock->expects($this->any())->method('close')->will($this->returnValue(True));
        $session = new Session($requestMock, $fileSessionHandlerMock);
        $reflection = new \ReflectionClass($session);
        $reflectionSessionId = $reflection->getProperty('sessionId');
        $reflectionSessionId->setAccessible(True);
        $session->open();
        $this->assertSame($requestSessionId, $reflectionSessionId->getValue($session));
        $this->assertSame('bar', $session->get('foo'));
    }

    public function testOpenDoesNotReopenSession() {
        $session = $this->getBlackHoleSession();
        $reflection = new \ReflectionClass($session);
        $reflectionSessionId = $reflection->getProperty('sessionId');
        $reflectionSessionId->setAccessible(True);
        $preId = $reflectionSessionId->getValue($session);
        $reflectionIsOpen = $reflection->getProperty('isOpen');
        $reflectionIsOpen->setAccessible(True);
        $reflectionIsOpen->setValue($session, True);
        $session->open();
        $postId = $reflectionSessionId->getValue($session);
        $this->assertSame($preId, $postId);
    }

    public function testOpenImportsSessionFromRequestWithCheckRefererWorks() {
        $requestSessionId = 42;
        $referer = 'foo.baz';
        $requestMock = $this->getRequestMock();
        $requestMock->expects($this->any())->method('hasCookie')->will($this->returnValue(True));
        $requestMock->expects($this->any())->method('getCookie')->will($this->returnValue($requestSessionId));
        $requestMock->expects($this->any())->method('hasHeader')->will($this->returnValue(True));
        $requestMock->expects($this->any())->method('getHeader')->will($this->returnValue($referer));
        $fileSessionHandlerMock = $this->getFileSessionHandlerMock();
        $fileSessionHandlerMock->expects($this->any())->method('exists')->will($this->returnValue(True));
        $fileSessionHandlerMock->expects($this->any())->method('open')->will($this->returnValue(True));
        $fileSessionHandlerMock->expects($this->any())->method('read')->will($this->returnValue(['foo'=>'bar']));
        $fileSessionHandlerMock->expects($this->any())->method('save')->will($this->returnValue(True));
        $fileSessionHandlerMock->expects($this->any())->method('close')->will($this->returnValue(True));
        $session = new Session($requestMock, $fileSessionHandlerMock);
        $session->setOption('referer_check', $referer);
        $reflection = new \ReflectionClass($session);
        $reflectionSessionId = $reflection->getProperty('sessionId');
        $reflectionSessionId->setAccessible(True);
        $session->open();
        $this->assertSame($requestSessionId, $reflectionSessionId->getValue($session));
        $this->assertSame('bar', $session->get('foo'));
    }

    public function testOpenWithCheckRefererGeneratesIdIfRefererCheckFails() {
        $requestSessionId = 42;
        $requestMock = $this->getRequestMock();
        $requestMock->expects($this->any())->method('hasCookie')->will($this->returnValue(True));
        $requestMock->expects($this->any())->method('hasHeader')->will($this->returnValue(True));
        $requestMock->expects($this->any())->method('getHeader')->will($this->returnValue('bar.qux'));
        $session = new Session($requestMock, $this->getBlackHoleFileSessionHandler());
        $reflection = new \ReflectionClass($session);
        $reflectionSessionId = $reflection->getProperty('sessionId');
        $reflectionSessionId->setAccessible(True);
        $session->setOption('referer_check', 'foo.baz');
        $session->open();
        $this->assertNotSame($requestSessionId, $reflectionSessionId->getValue($session));
    }

    public function testOpenWithCheckRefererGeneratesIdIfRefererFieldIsMissingInRequest() {
        $requestSessionId = 42;
        $requestMock = $this->getRequestMock();
        $requestMock->expects($this->any())->method('hasCookie')->will($this->returnValue(True));
        $requestMock->expects($this->any())->method('hasHeader')->will($this->returnValue(False));
        $session = new Session($requestMock, $this->getBlackHoleFileSessionHandler());
        $reflection = new \ReflectionClass($session);
        $reflectionSessionId = $reflection->getProperty('sessionId');
        $reflectionSessionId->setAccessible(True);
        $session->setOption('referer_check', 'foo.baz');
        $session->open();
        $this->assertNotSame($requestSessionId, $reflectionSessionId->getValue($session));
    }

    public function testSetSetter() {
        $requestMock = $this->getRequestMock();
        $session = new Session($requestMock, $this->getBlackHoleFileSessionHandler());
        $reflectionSession = new \ReflectionClass($session);
        $reflectionIsOpen = $reflectionSession->getProperty('isOpen');
        $reflectionIsOpen->setAccessible(True);
        $this->assertFalse($reflectionIsOpen->getValue($session));
        $session->set('Foo', 'Bar');
        $this->assertTrue($reflectionIsOpen->getValue($session));
    }

    /**
    * @expectedException \DomainException
    * @expectedExceptionMessage Session field does not exist: Foo
    */
    public function testGetThrowsDomainExceptionIfFieldNotSet() {
        $session = $this->getBlackHoleSession();
        $session->get('Foo');
    }

    public function testGetGetter() {
        $session = $this->getBlackHoleSession();
        $session->set('Foo', 'bar');
        $this->assertSame('bar', $session->get('Foo'));
    }

    public function testHasReturnsTrueIfHasField() {
        $session = $this->getBlackHoleSession();
        $session->set('Foo', 'bar');
        $this->assertTrue($session->has('Foo'));
    }

    public function testHasReturnTrueIfMissingField() {
        $session = $this->getBlackHoleSession();
        $this->assertFalse($session->has('Foo'));
    }

    public function testGetAllOpenSessionIfNotOpen() {
        $session = $this->getBlackHoleSession();
        $reflectionSession = new \ReflectionClass($session);
        $reflectionIsOpen = $reflectionSession->getProperty('isOpen');
        $reflectionIsOpen->setAccessible(True);
        $this->assertFalse($reflectionIsOpen->getValue($session));
        $session->getAll();
        $postIsOpen = $reflectionIsOpen->getValue($session);
        $this->assertTrue($postIsOpen);
    }

    public function testGetAllGetsAll() {
        $session = $this->getBlackHoleSession();
        $session->set('Foo', 'Bar');
        $session->set('Baz', 'Qux');
        $data = $session->getAll();
        $this->assertSame('Bar', $data['Foo']);
        $this->assertSame('Qux', $data['Baz']);
    }

    /**
    * @expectedException Arya\Sessions\SessionException
    * @expectedExceptionMessage Failed closing session
    */
    public function testCloseThrowsSessionExceptionIfUnableToClose() {
        $fileSessionHandlerMock = $this->getFileSessionHandlerMock();
        $fileSessionHandlerMock->expects($this->any())->method('open')->will($this->returnValue(True));
        $fileSessionHandlerMock->expects($this->any())->method('save')->will($this->returnValue(True));
        $fileSessionHandlerMock->expects($this->any())->method('write')->will($this->returnValue(True));
        $fileSessionHandlerMock->expects($this->any())->method('close')->will($this->returnValue(False));
        $session = new Session($this->getRequestMock(), $fileSessionHandlerMock);
        $session->set('Foo', 'Bar');
        $session->close();
    }

    /**
    * @expectedException Arya\Sessions\SessionException
    * @expectedExceptionMessage Failed saving session data
    */
    public function testSaveThrowsExceptionIfUnableToWrite() {
        $fileSessionHandlerMock = $this->getFileSessionHandlerMock();
        $fileSessionHandlerMock->expects($this->any())->method('open')->will($this->returnValue(True));
        $fileSessionHandlerMock->expects($this->any())->method('save')->will($this->returnValue(True));
        $fileSessionHandlerMock->expects($this->any())->method('write')->will($this->returnValue(False));
        $fileSessionHandlerMock->expects($this->any())->method('close')->will($this->returnValue(True));
        $session = new Session($this->getRequestMock(), $fileSessionHandlerMock);
        $session->set('Foo', 'Bar');
        $session->save();
    }

    public function testCloseCallsInternalGarbageCollectionWithGarbageCollectionMaxLifeTime() {
        $garbageCollectionMaxLifeTime = 50;
        $requestMock = $this->getRequestMock();
        $fileSessionHandlerMock = $this->getBlackHoleFileSessionHandler();
        $fileSessionHandlerMock->expects($this->any())->method('gc')->with($this->equalTo($garbageCollectionMaxLifeTime))->will($this->returnValue(True));
        $session = new Session($requestMock, $fileSessionHandlerMock);
        $session->set('Foo', 'Bar');
        $session->setOption('gc_divisor', 1); // 100% chance of gc
        $session->setOption('gc_probability', 1);
        $session->setOption('gc_maxlifetime', 50);
        $session->open();
        $session->close();
    }

    public function testShouldSetCookieReturnsTrueIfDataHasSet() {
        $session = $this->getBlackHoleSession();
        $session->set('Foo', 'Bar');
        $this->assertTrue($session->ShouldSetCookie());
    }

    public function testShouldSetCookieReturnsFalseIfClean() {
        $session = $this->getBlackHoleSession();
        $this->assertFalse($session->ShouldSetCookie());
    }

    public function testGetCookieElementsReturnsArrayWithCookieElements() {
        $session = $this->getBlackHoleSession();
        $session->setOption('cookie_path', '/');
        $session->setOption('cookie_domain', 'foo.bar');
        //$session->setOption('cookie_lifetime', 10); <-- Missing setOption for cookie_lifetime
        $this->assertSame(
            [
                'ASESSID',
                NULL, // sessionID
                [
                    'domain' => 'foo.bar',
                    'path' => '/',
                    'expire' => 0,
                    'secure' => false,
                    'httponly' => True
                ]
            ],
            $session->getCookieElements()
        );
    }

    public function testRegenerateDeletesOldSessionAndStartsNew() {
        $session = $this->getBlackHoleSession();
        $reflection = new \ReflectionClass($session);
        $reflectionData = $reflection->getProperty('isOpen');
        $reflectionData->setAccessible(True);
        $reflectionData->setValue($session, []);
        $reflectionSessionId = $reflection->getProperty('sessionId');
        $reflectionSessionId->setAccessible(True);
        $reflectionSessionId->setValue($session, 42);
        $session->regenerate();
        $this->assertFalse($session->has('Foo'));
    }

    private function getBlackHoleSession() {
        return new Session($this->getRequestMock(), $this->getBlackHoleFileSessionHandler());
    }

    private function getBlackHoleFileSessionHandler() {
        $fileSessionHandlerMock = $this->getFileSessionHandlerMock();
        $fileSessionHandlerMock->expects($this->any())->method('exists')->will($this->returnValue(True));
        $fileSessionHandlerMock->expects($this->any())->method('open')->will($this->returnValue(True));
        $fileSessionHandlerMock->expects($this->any())->method('write')->will($this->returnValue(True));
        $fileSessionHandlerMock->expects($this->any())->method('save')->will($this->returnValue(True));
        $fileSessionHandlerMock->expects($this->any())->method('close')->will($this->returnValue(True));
        $fileSessionHandlerMock->expects($this->any())->method('destroy')->will($this->returnValue(True));

        return $fileSessionHandlerMock;
    }

    private function getRequestMock() {
        $requestStubBuilder = $this->getMockBuilder('\Arya\Request');
        $requestStubBuilder->disableOriginalConstructor();

        return $requestStubBuilder->getMock();
    }

    private function getFileSessionHandlerMock() {
        $fileSessionHandlerStubBuilder = $this->getMockBuilder('\Arya\Sessions\FileSessionHandler');
        $fileSessionHandlerStubBuilder->disableOriginalConstructor();

        return  $fileSessionHandlerStubBuilder->getMock();
    }

    public function testOffsetGetGetter() {
        $session = $this->getBlackHoleSession();
        $session->set('Foo', 'Bar');
        $this->assertSame('Bar', $session['Foo']);
    }

    public function testOffsetSetSetter() {
        $session = $this->getBlackHoleSession();
        $session['Foo'] = 'Bar';
        $this->assertSame('Bar', $session->get('Foo'));
    }

    public function testOffsetExists() {
        $session = $this->getBlackHoleSession();
        $this->assertFalse($session->offsetExists('Foo'));
        $session['Foo'] = 'Bar';
        $this->assertTrue($session->offsetExists('Foo'));
    }

    public function testOffsetUnsetOpenSessionIfClosed() {
        $session = $this->getBlackHoleSession();
        $reflection = new \ReflectionClass($session);
        $reflectionIsOpen = $reflection->getProperty('isOpen');
        $reflectionIsOpen->setAccessible(True);
        $this->assertFalse($reflectionIsOpen->getValue($session));
        unset($session['Foo']);
        $this->assertTrue($reflectionIsOpen->getValue($session));
    }

    public function testOffsetUnset() {
        $session = $this->getBlackHoleSession();
        $session['Foo'] = 'Bar';
        $this->assertTrue($session->has('Foo'));
        unset($session['Foo']);
        $this->assertFalse($session->has('Foo'));
    }

    public function testImplementsIterator() {
        $session = $this->getBlackHoleSession();
        $session['Foo'] = 'Bar';
        $session['Bar'] = 'Foo';
        $i = 0;
        foreach($session as  $key => $value) {
            ++$i;
        }
        $this->assertSame(2, $i);
    }
}
