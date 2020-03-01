<?php

declare(strict_types=1);

namespace Dotenv\Tests;

use Dotenv\Result\Error;
use Dotenv\Result\Success;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ResultTest extends TestCase
{
    public function testSuccessValue()
    {
        $this->assertTrue(Success::create('foo')->error()->isEmpty());
        $this->assertTrue(Success::create('foo')->success()->isDefined());
        $this->assertEquals('foo', Success::create('foo')->success()->get());
    }

    public function testSuccessMapping()
    {
        $r = Success::create('foo')
            ->map('strtoupper')
            ->mapError('ucfirst');

        $this->assertTrue($r->success()->isDefined());
        $this->assertEquals('FOO', $r->success()->get());
    }

    public function testSuccessFlatMappingSuccess()
    {
        $r = Success::create('foo')->flatMap(function (string $data) {
            return Success::create('OH YES');
        });

        $this->assertTrue($r->success()->isDefined());
        $this->assertEquals('OH YES', $r->success()->get());
    }

    public function testSuccessFlatMappingError()
    {
        $r = Success::create('foo')->flatMap(function (string $data) {
            return Error::create('OH NO');
        });

        $this->assertTrue($r->error()->isDefined());
        $this->assertEquals('OH NO', $r->error()->get());
    }

    public function testSuccessFail()
    {
        $result = Success::create('foo');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('None has no value.');

        $result->error()->get();
    }

    public function testErrorValue()
    {
        $this->assertTrue(Error::create('foo')->error()->isDefined());
        $this->assertTrue(Error::create('foo')->success()->isEmpty());
        $this->assertEquals('foo', Error::create('foo')->error()->get());
    }

    public function testErrorMapping()
    {
        $r = Error::create('foo')
            ->map('strtoupper')
            ->mapError('ucfirst');

        $this->assertEquals('Foo', $r->error()->get());
    }

    public function testErrorFail()
    {
        $result = Error::create('foo');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('None has no value.');

        $result->success()->get();
    }
}
