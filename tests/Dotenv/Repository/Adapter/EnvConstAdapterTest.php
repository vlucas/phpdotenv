<?php

declare(strict_types=1);

namespace Dotenv\Tests\Repository\Adapter;

use Dotenv\Repository\Adapter\EnvConstAdapter;
use PHPUnit\Framework\TestCase;

class EnvConstAdapterTest extends TestCase
{
    public function testGoodRead()
    {
        $_ENV['CONST_TEST'] = 'foo bar baz';
        $value = self::createAdapter()->read('CONST_TEST');
        $this->assertTrue($value->isDefined());
        $this->assertSame('foo bar baz', $value->get());
    }

    public function testBadTypeRead()
    {
        $_ENV['CONST_TEST'] = 123;
        $value = self::createAdapter()->read('CONST_TEST');
        $this->assertFalse($value->isDefined());
    }

    public function testUndefinedRead()
    {
        unset($_ENV['CONST_TEST']);
        $value = self::createAdapter()->read('CONST_TEST');
        $this->assertFalse($value->isDefined());
    }

    public function testGoodWrite()
    {
        $this->assertTrue(self::createAdapter()->write('CONST_TEST', 'foo'));
        $this->assertSame('foo', $_ENV['CONST_TEST']);
    }

    public function testEmptyWrite()
    {
        $this->assertTrue(self::createAdapter()->write('CONST_TEST', ''));
        $this->assertSame('', $_ENV['CONST_TEST']);
    }

    public function testNullWrite()
    {
        $this->assertTrue(self::createAdapter()->write('CONST_TEST', null));
        $this->assertSame(null, $_ENV['CONST_TEST']);
    }

    public function testGoodDelete()
    {
        $this->assertTrue(self::createAdapter()->delete('CONST_TEST'));
        $this->assertFalse(isset($_ENV['CONST_TEST']));
    }

    private static function createAdapter()
    {
        return EnvConstAdapter::create()->get();
    }
}
