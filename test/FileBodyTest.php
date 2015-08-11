<?php

namespace Arya\Test;

use Arya\FileBody;

class JsonFileBodyTest extends \PHPUnit_Framework_TestCase {

    private  $fileBody;

    public function setup() {
        $this->fileBody = new FileBody(__FILE__);
    }
    /**
    * @expectedException \RuntimeException
    * @expectedExceptionMessage FileBody path must be a string filesystem path; array specified
    */
    public function testConstructThrowsRuntimeExceptionIfArgumentNotString() {
        new FileBody([]);
    }

    /**
    * @expectedException \RuntimeException
    * @expectedExceptionMessage FileBody path is not readable: unreadable
    */
    public function testConstructThrowsRuntimeExceptionIfPathNotReadable() {
        new FileBody('unreadable');
    }

    /**
    * @expectedException \RuntimeException
    * @expectedExceptionMessage FileBody path is not a file: ./
    */
    public function testConstructThrowsRuntimeException() {
        new FileBody('./');
    }

    public function testConstructorArgumentsSetsPath() {
        $this->assertSame(__FILE__, $this->fileBody->getPath());
    }

    public function testgetHeadersReturnCorrectContentLength() {
        $this->assertSame(
            filesize(__FILE__),
            $this->fileBody->getHeaders()['Content-Length']
        );
    }

}
