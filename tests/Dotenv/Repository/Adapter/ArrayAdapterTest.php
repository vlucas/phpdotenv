<?php

declare(strict_types=1);

namespace Dotenv\Tests\Repository\Adapter;

use Dotenv\Repository\Adapter\ArrayAdapter;
use PHPUnit\Framework\TestCase;

final class ArrayAdapterTest extends TestCase
{
    public function testGoodRead()
    {
        $adapter = self::createAdapter();
        $adapter->write('CONST_TEST', 'foo bar baz');
        $value = $adapter->read('CONST_TEST');
        $this->assertTrue($value->isDefined());
        $this->assertSame('foo bar baz', $value->get());
    }

    public function testUndefinedRead()
    {
        $adapter = self::createAdapter();
        unset($_ENV['CONST_TEST']);
        $value = $adapter->read('CONST_TEST');
        $this->assertFalse($value->isDefined());
    }

    public function testGoodWrite()
    {
        $adapter = self::createAdapter();
        $this->assertTrue($adapter->write('CONST_TEST', 'foo'));
        $this->assertSame('foo', $adapter->read('CONST_TEST')->get());
    }

    public function testEmptyWrite()
    {
        $adapter = self::createAdapter();
        $this->assertTrue($adapter->write('CONST_TEST', ''));
        $this->assertSame('', $adapter->read('CONST_TEST')->get());
    }

    public function testGoodDelete()
    {
        $adapter = self::createAdapter();
        $this->assertTrue($adapter->delete('CONST_TEST'));
        $this->assertFalse($adapter->read('CONST_TEST')->isDefined());
    }

    private static function createAdapter()
    {
        return ArrayAdapter::create()->get();
    }
}
