<?php

declare(strict_types=1);

namespace Dotenv\Tests\Repository\Adapter;

use Dotenv\Repository\Adapter\ServerConstAdapter;
use PHPUnit\Framework\TestCase;

final class ServerConstAdapterTest extends TestCase
{
    public function testGoodRead()
    {
        $_SERVER['CONST_TEST'] = 'foo bar baz';
        $value = self::createAdapter()->read('CONST_TEST');
        $this->assertTrue($value->isDefined());
        $this->assertSame('foo bar baz', $value->get());
    }

    public function testBadTypeRead()
    {
        $_SERVER['CONST_TEST'] = 123;
        $value = self::createAdapter()->read('CONST_TEST');
        $this->assertFalse($value->isDefined());
    }

    public function testUndefinedRead()
    {
        unset($_SERVER['CONST_TEST']);
        $value = self::createAdapter()->read('CONST_TEST');
        $this->assertFalse($value->isDefined());
    }

    public function testGoodWrite()
    {
        $this->assertTrue(self::createAdapter()->write('CONST_TEST', 'foo'));
        $this->assertSame('foo', $_SERVER['CONST_TEST']);
    }

    public function testEmptyWrite()
    {
        $this->assertTrue(self::createAdapter()->write('CONST_TEST', ''));
        $this->assertSame('', $_SERVER['CONST_TEST']);
    }

    public function testGoodDelete()
    {
        $this->assertTrue(self::createAdapter()->delete('CONST_TEST'));
        $this->assertFalse(isset($_SERVER['CONST_TEST']));
    }

    private static function createAdapter()
    {
        return ServerConstAdapter::create()->get();
    }
}
