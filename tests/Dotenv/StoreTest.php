<?php

declare(strict_types=1);

namespace Dotenv\Tests;

use Dotenv\Store\File\Paths;
use Dotenv\Store\File\Reader;
use Dotenv\Store\StoreBuilder;
use PHPUnit\Framework\TestCase;

class StoreTest extends TestCase
{
    /**
     * @var string
     */
    private static $folder;

    /**
     * @beforeClass
     */
    public static function setFolder()
    {
        self::$folder = dirname(__DIR__).'/fixtures/env';
    }

    public function testBasicReadDirect()
    {
        $this->assertSame(
            [
                self::$folder.DIRECTORY_SEPARATOR.'.env' => "FOO=bar\nBAR=baz\nSPACED=\"with spaces\"\n\nNULL=\n",
            ],
            Reader::read(
                Paths::filePaths([self::$folder], ['.env'])
            )
        );
    }

    public function testBasicRead()
    {
        $builder = StoreBuilder::createWithDefaultName()
            ->addPath(self::$folder);

        $this->assertSame(
            "FOO=bar\nBAR=baz\nSPACED=\"with spaces\"\n\nNULL=\n",
            $builder->make()->read()
        );
    }

    public function testFileReadMultipleShortCircuitModeDirect()
    {
        $this->assertSame(
            [
                self::$folder.DIRECTORY_SEPARATOR.'.env' => "FOO=bar\nBAR=baz\nSPACED=\"with spaces\"\n\nNULL=\n",
            ],
            Reader::read(
                Paths::filePaths([self::$folder], ['.env', 'example.env'])
            )
        );
    }

    public function testFileReadMultipleShortCircuitMode()
    {
        $builder = StoreBuilder::createWithNoNames()
            ->addPath(self::$folder)
            ->addName('.env')
            ->addName('example.env')
            ->shortCircuit();

        $this->assertSame(
            "FOO=bar\nBAR=baz\nSPACED=\"with spaces\"\n\nNULL=\n",
            $builder->make()->read()
        );
    }

    public function testFileReadMultipleWithoutShortCircuitModeDirect()
    {
        $this->assertSame(
            [
                self::$folder.DIRECTORY_SEPARATOR.'.env'        => "FOO=bar\nBAR=baz\nSPACED=\"with spaces\"\n\nNULL=\n",
                self::$folder.DIRECTORY_SEPARATOR.'example.env' => "EG=\"example\"\n",
            ],
            Reader::read(
                Paths::filePaths([self::$folder], ['.env', 'example.env']),
                false
            )
        );
    }

    public function testFileReadMultipleWithoutShortCircuitMode()
    {
        $builder = StoreBuilder::createWithDefaultName()
            ->addPath(self::$folder)
            ->addName('example.env');

        $this->assertSame(
            "FOO=bar\nBAR=baz\nSPACED=\"with spaces\"\n\nNULL=\n\nEG=\"example\"\n",
            $builder->make()->read()
        );
    }
}
