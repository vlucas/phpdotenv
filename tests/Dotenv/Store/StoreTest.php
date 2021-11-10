<?php

declare(strict_types=1);

namespace Dotenv\Tests\Store;

use Dotenv\Exception\InvalidEncodingException;
use Dotenv\Store\File\Paths;
use Dotenv\Store\File\Reader;
use Dotenv\Store\StoreBuilder;
use PHPUnit\Framework\TestCase;

final class StoreTest extends TestCase
{
    /**
     * @var string
     */
    private static $folder;

    /**
     * @beforeClass
     *
     * @return void
     */
    public static function setFolder()
    {
        self::$folder = \dirname(\dirname(__DIR__)).'/fixtures/env';
    }

    public function testBasicReadDirect()
    {
        self::assertSame(
            [
                self::$folder.\DIRECTORY_SEPARATOR.'.env' => "FOO=bar\nBAR=baz\nSPACED=\"with spaces\"\n\nNULL=\n",
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

        self::assertSame(
            "FOO=bar\nBAR=baz\nSPACED=\"with spaces\"\n\nNULL=\n",
            $builder->make()->read()
        );
    }

    public function testBasicReadWindowsEncoding()
    {
        $builder = StoreBuilder::createWithNoNames()
            ->addPath(self::$folder)
            ->addName('windows.env')
            ->fileEncoding('Windows-1252');

        self::assertSame(
            "MBW=\"ñá\"\n",
            $builder->make()->read()
        );
    }

    public function testBasicReadBadEncoding()
    {
        $builder = StoreBuilder::createWithNoNames()
            ->addPath(self::$folder)
            ->addName('windows.env')
            ->fileEncoding('Windowss-1252');

        $this->expectException(InvalidEncodingException::class);
        $this->expectExceptionMessage('Illegal character encoding [Windowss-1252] specified.');

        $builder->make()->read();
    }

    public function testFileReadMultipleShortCircuitModeDirect()
    {
        self::assertSame(
            [
                self::$folder.\DIRECTORY_SEPARATOR.'.env' => "FOO=bar\nBAR=baz\nSPACED=\"with spaces\"\n\nNULL=\n",
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

        self::assertSame(
            "FOO=bar\nBAR=baz\nSPACED=\"with spaces\"\n\nNULL=\n",
            $builder->make()->read()
        );
    }

    public function testFileReadMultipleWithoutShortCircuitModeDirect()
    {
        self::assertSame(
            [
                self::$folder.\DIRECTORY_SEPARATOR.'.env'        => "FOO=bar\nBAR=baz\nSPACED=\"with spaces\"\n\nNULL=\n",
                self::$folder.\DIRECTORY_SEPARATOR.'example.env' => "EG=\"example\"\n",
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

        self::assertSame(
            "FOO=bar\nBAR=baz\nSPACED=\"with spaces\"\n\nNULL=\n\nEG=\"example\"\n",
            $builder->make()->read()
        );
    }
    public function testFileReadWithUtf8WithBomEncoding()
    {
        self::assertSame(
            [
                self::$folder.\DIRECTORY_SEPARATOR.'utf8-with-bom-encoding.env' => "FOO=bar\nBAR=baz\nSPACED=\"with spaces\"\n",
            ],
            Reader::read(
                Paths::filePaths([self::$folder], ['utf8-with-bom-encoding.env'])
            )
        );
    }
}
