<?php

use Dotenv\File\Paths;
use Dotenv\File\Reader;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    /**
     * @var string
     */
    private $folder;

    public function setUp()
    {
        $this->folder = dirname(__DIR__).'/fixtures/env';
    }

    public function testBasicRead()
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

    public function testFileReadMultipleNotShortCircuitMode()
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

    public function testFileReadMultipleWithShortCircuitMode()
    {
        $this->assertSame(
            [
                $this->folder.DIRECTORY_SEPARATOR.'.env' => "FOO=bar\nBAR=baz\nSPACED=\"with spaces\"\n\nNULL=\n",
                $this->folder.DIRECTORY_SEPARATOR.'example.env' => "EG=\"example\"\n",
            ],
            Reader::read(
                Paths::filePaths([$this->folder], ['.env', 'example.env']),
                false
            )
        );
    }
}
