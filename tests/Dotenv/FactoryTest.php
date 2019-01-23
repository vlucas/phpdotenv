<?php

use Dotenv\Environment\Adapter\EnvConstAdapter;
use Dotenv\Environment\DotenvFactory;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    private static function getAdapters($obj)
    {
        $prop = (new ReflectionClass($obj))->getProperty('adapters');

        $prop->setAccessible(true);

        return $prop->getValue($obj);
    }

    public function testDefaults()
    {
        $f = new DotenvFactory();

        $this->assertInstanceOf('Dotenv\Environment\FactoryInterface', $f);
        $this->assertCount(3, self::getAdapters($f->create()));
        $this->assertCount(3, self::getAdapters($f->createImmutable()));
    }

    public function testSingle()
    {
        $f = new DotenvFactory([new EnvConstAdapter()]);

        $this->assertInstanceOf('Dotenv\Environment\FactoryInterface', $f);
        $this->assertCount(1, self::getAdapters($f->create()));
        $this->assertCount(1, self::getAdapters($f->createImmutable()));
    }

    public function testNone()
    {
        $f = new DotenvFactory([]);

        $this->assertInstanceOf('Dotenv\Environment\FactoryInterface', $f);
        $this->assertCount(0, self::getAdapters($f->create()));
        $this->assertCount(0, self::getAdapters($f->createImmutable()));
    }
}
