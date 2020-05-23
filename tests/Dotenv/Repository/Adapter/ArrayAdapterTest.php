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
        self::assertTrue($value->isDefined());
        self::assertSame('foo bar baz', $value->get());
    }

    public function testUndefinedRead()
    {
        $adapter = self::createAdapter();
        unset($_ENV['CONST_TEST']);
        $value = $adapter->read('CONST_TEST');
        self::assertFalse($value->isDefined());
    }

    public function testGoodWrite()
    {
        $adapter = self::createAdapter();
        self::assertTrue($adapter->write('CONST_TEST', 'foo'));
        self::assertSame('foo', $adapter->read('CONST_TEST')->get());
    }

    public function testEmptyWrite()
    {
        $adapter = self::createAdapter();
        self::assertTrue($adapter->write('CONST_TEST', ''));
        self::assertSame('', $adapter->read('CONST_TEST')->get());
    }

    public function testGoodDelete()
    {
        $adapter = self::createAdapter();
        self::assertTrue($adapter->delete('CONST_TEST'));
        self::assertFalse($adapter->read('CONST_TEST')->isDefined());
    }

    /**
     * @return \Dotenv\Repository\Adapter\AdapterInterface
     */
    private static function createAdapter()
    {
        return ArrayAdapter::create()->get();
    }
}
