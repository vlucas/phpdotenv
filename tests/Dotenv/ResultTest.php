<?php

use Dotenv\Regex\Error;
use Dotenv\Regex\Success;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    public function testSuccessValue()
    {
        $this->assertTrue(Success::create('foo')->error()->isEmpty());
        $this->assertTrue(Success::create('foo')->success()->isDefined());
        $this->assertEquals('foo', Success::create('foo')->getSuccess());
    }

    public function testSuccessMapping()
    {
        $r = Success::create('foo')
            ->mapSuccess('strtoupper')
            ->mapError('ucfirst');

        $this->assertEquals('FOO', $r->getSuccess());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage None has no value.
     */
    public function testSuccessFail()
    {
        Success::create('foo')->getError();
    }

    public function testErrorValue()
    {
        $this->assertTrue(Error::create('foo')->error()->isDefined());
        $this->assertTrue(Error::create('foo')->success()->isEmpty());
        $this->assertEquals('foo', Error::create('foo')->getError());
    }

    public function testErrorMapping()
    {
        $r = Error::create('foo')
            ->mapSuccess('strtoupper')
            ->mapError('ucfirst');

        $this->assertEquals('Foo', $r->getError());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage None has no value.
     */
    public function testErrorFail()
    {
        Error::create('foo')->getSuccess();
    }
}
