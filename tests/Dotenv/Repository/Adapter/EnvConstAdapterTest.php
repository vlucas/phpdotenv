<?php

declare(strict_types=1);

namespace Dotenv\Tests\Repository\Adapter;

use Dotenv\Repository\Adapter\EnvConstAdapter;
use PHPUnit\Framework\TestCase;

final class EnvConstAdapterTest extends TestCase
{
    public function testGoodRead()
    {
        $_ENV['CONST_TEST'] = 'foo bar baz';
        $value = self::createAdapter()->read('CONST_TEST');
        self::assertTrue($value->isDefined());
        self::assertSame('foo bar baz', $value->get());
    }

    public function testFalseRead()
    {
        $_ENV['CONST_TEST'] = false;
        $value = self::createAdapter()->read('CONST_TEST');
        self::assertTrue($value->isDefined());
        self::assertSame('false', $value->get());
    }

    public function testTrueRead()
    {
        $_ENV['CONST_TEST'] = true;
        $value = self::createAdapter()->read('CONST_TEST');
        self::assertTrue($value->isDefined());
        self::assertSame('true', $value->get());
    }

    public function testBadTypeRead()
    {
        $_ENV['CONST_TEST'] = [123];
        $value = self::createAdapter()->read('CONST_TEST');
        self::assertFalse($value->isDefined());
    }

    public function testUndefinedRead()
    {
        unset($_ENV['CONST_TEST']);
        $value = self::createAdapter()->read('CONST_TEST');
        self::assertFalse($value->isDefined());
    }

    public function testGoodWrite()
    {
        self::assertTrue(self::createAdapter()->write('CONST_TEST', 'foo'));
        self::assertSame('foo', $_ENV['CONST_TEST']);
    }

    public function testEmptyWrite()
    {
        self::assertTrue(self::createAdapter()->write('CONST_TEST', ''));
        self::assertSame('', $_ENV['CONST_TEST']);
    }

    public function testGoodDelete()
    {
        self::assertTrue(self::createAdapter()->delete('CONST_TEST'));
        self::assertFalse(isset($_ENV['CONST_TEST']));
    }

    /**
     * @return \Dotenv\Repository\Adapter\AdapterInterface
     */
    private static function createAdapter()
    {
        return EnvConstAdapter::create()->get();
    }
}
