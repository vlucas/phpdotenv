<?php

namespace Dotenv\Tests;

use Dotenv\Result\Error;
use Dotenv\Result\Success;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    public function testSuccessValue()
    {
        self::assertTrue(Success::create('foo')->error()->isEmpty());
        self::assertTrue(Success::create('foo')->success()->isDefined());
        self::assertEquals('foo', Success::create('foo')->getSuccess());
    }

    public function testSuccessMapping()
    {
        $r = Success::create('foo')
            ->mapSuccess('strtoupper')
            ->mapError('ucfirst');

        self::assertEquals('FOO', $r->getSuccess());
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
        self::assertTrue(Error::create('foo')->error()->isDefined());
        self::assertTrue(Error::create('foo')->success()->isEmpty());
        self::assertEquals('foo', Error::create('foo')->getError());
    }

    public function testErrorMapping()
    {
        $r = Error::create('foo')
            ->mapSuccess('strtoupper')
            ->mapError('ucfirst');

        self::assertEquals('Foo', $r->getError());
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
