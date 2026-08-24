<?php

declare(strict_types=1);

namespace Dotenv\Tests\Repository\Adapter;

use Dotenv\Repository\Adapter\PutenvAdapter;
use PHPUnit\Framework\TestCase;

final class PutenvAdapterTest extends TestCase
{
    public function testGoodRead()
    {
        \putenv('CONST_TEST=foo bar baz');
        $value = self::createAdapter()->read('CONST_TEST');
        self::assertTrue($value->isDefined());
        self::assertSame('foo bar baz', $value->get());
    }

    public function testUndefinedRead()
    {
        \putenv('CONST_TEST');
        $value = self::createAdapter()->read('CONST_TEST');
        self::assertFalse($value->isDefined());
    }

    public function testGoodWrite()
    {
        self::assertTrue(self::createAdapter()->write('CONST_TEST', 'foo'));
        self::assertSame('foo', \getenv('CONST_TEST'));
    }

    public function testEmptyWrite()
    {
        self::assertTrue(self::createAdapter()->write('CONST_TEST', ''));
        self::assertSame('', \getenv('CONST_TEST'));
    }

    public function testGoodDelete()
    {
        self::assertTrue(self::createAdapter()->delete('CONST_TEST'));
        self::assertFalse(\getenv('CONST_TEST'));
    }

    public function testNullByteNameWrite()
    {
        \putenv('CONST_NUL_A=orig');
        self::assertFalse(self::createAdapter()->write("CONST_NUL_A\0X", 'value'));
        self::assertSame('orig', \getenv('CONST_NUL_A'));
    }

    public function testNullByteValueWrite()
    {
        \putenv('CONST_NUL_D=orig');
        self::assertFalse(self::createAdapter()->write('CONST_NUL_D', "a\0b"));
        self::assertSame('orig', \getenv('CONST_NUL_D'));
    }

    public function testNullByteNameRead()
    {
        \putenv('CONST_NUL_C=orig');
        self::assertFalse(self::createAdapter()->read("CONST_NUL_C\0X")->isDefined());
    }

    public function testNullByteNameDelete()
    {
        \putenv('CONST_NUL_B=orig');
        self::assertFalse(self::createAdapter()->delete("CONST_NUL_B\0X"));
        self::assertSame('orig', \getenv('CONST_NUL_B'));
    }

    /**
     * @return \Dotenv\Repository\Adapter\AdapterInterface
     */
    private static function createAdapter()
    {
        return PutenvAdapter::create()->get();
    }
}
