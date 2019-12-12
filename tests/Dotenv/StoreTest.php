<?php

use Dotenv\Store\File\Paths;
use Dotenv\Store\File\Reader;
use Dotenv\Store\StoreBuilder;
use PHPUnit\Framework\TestCase;

class StoreTest extends TestCase
{
    /**
     * @var string
     */
    private $folder;

    public function setUp()
    {
        $this->folder = dirname(__DIR__).'/fixtures/env';
    }

    public function testBasicReadDirect()
    {
        $this->assertSame(
            [
                $this->folder.DIRECTORY_SEPARATOR.'.env' => "FOO=bar\nBAR=baz\nSPACED=\"with spaces\"\n\nNULL=\n",
            ],
            Reader::read(
                Paths::filePaths([$this->folder], ['.env'])
            )
        );
    }

    public function testBasicRead()
    {
        $builder = StoreBuilder::create()
            ->withPaths([$this->folder])
            ->withNames(['.env']);

        $this->assertSame(
            "FOO=bar\nBAR=baz\nSPACED=\"with spaces\"\n\nNULL=\n",
            $builder->make()->read()
        );
    }

    public function testFileReadMultipleShortCircuitModeDirect()
    {
        $this->assertSame(
            [
                $this->folder.DIRECTORY_SEPARATOR.'.env' => "FOO=bar\nBAR=baz\nSPACED=\"with spaces\"\n\nNULL=\n",
            ],
            Reader::read(
                Paths::filePaths([$this->folder], ['.env', 'example.env'])
            )
        );
    }

    public function testFileReadMultipleShortCircuitMode()
    {
        $builder = StoreBuilder::create()
            ->withPaths([$this->folder])
            ->withNames(['.env', 'example.env'])
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
                $this->folder.DIRECTORY_SEPARATOR.'.env'        => "FOO=bar\nBAR=baz\nSPACED=\"with spaces\"\n\nNULL=\n",
                $this->folder.DIRECTORY_SEPARATOR.'example.env' => "EG=\"example\"\n",
            ],
            Reader::read(
                Paths::filePaths([$this->folder], ['.env', 'example.env']),
                false
            )
        );
    }

    public function testFileReadMultipleWithoutShortCircuitMode()
    {
        $builder = StoreBuilder::create()
            ->withPaths([$this->folder])
            ->withNames(['.env', 'example.env']);

        $this->assertSame(
            "FOO=bar\nBAR=baz\nSPACED=\"with spaces\"\n\nNULL=\n\nEG=\"example\"\n",
            $builder->make()->read()
        );
    }
}
