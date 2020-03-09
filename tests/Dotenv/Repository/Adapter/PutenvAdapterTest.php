<?php

declare(strict_types=1);

namespace Dotenv\Tests\Repository\Adapter;

use Dotenv\Repository\Adapter\PutenvAdapter;
use PHPUnit\Framework\TestCase;

class PutenvAdapterTest extends TestCase
{
    public function testGoodRead()
    {
        putenv('CONST_TEST=foo bar baz');
        $value = self::createAdapter()->read('CONST_TEST');
        $this->assertTrue($value->isDefined());
        $this->assertSame('foo bar baz', $value->get());
    }

    public function testUndefinedRead()
    {
        putenv('CONST_TEST');
        $value = self::createAdapter()->read('CONST_TEST');
        $this->assertFalse($value->isDefined());
    }

    public function testGoodWrite()
    {
        $this->assertTrue(self::createAdapter()->write('CONST_TEST', 'foo'));
        $this->assertSame('foo', getenv('CONST_TEST'));
    }

    public function testEmptyWrite()
    {
        $this->assertTrue(self::createAdapter()->write('CONST_TEST', ''));
        $this->assertSame('', getenv('CONST_TEST'));
    }

    public function testNullWrite()
    {
        $this->assertTrue(self::createAdapter()->write('CONST_TEST', null));
        $this->assertSame('', getenv('CONST_TEST'));
    }

    public function testGoodDelete()
    {
        $this->assertTrue(self::createAdapter()->delete('CONST_TEST'));
        $this->assertFalse(getenv('CONST_TEST'));
    }

    private static function createAdapter()
    {
        return PutenvAdapter::create()->get();
    }
}
