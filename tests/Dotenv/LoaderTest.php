<?php

use Dotenv\Loader;

class LoaderTest extends \PHPUnit_Framework_TestCase
{
    private $fixturesFolder;
    private $fixturesFolderWrong;

    public function setUp()
    {
        $this->fixturesFolder = dirname(__DIR__) . '/fixtures/env';
        $this->fixturesFolderWrong = dirname(__DIR__) . '/fixtures/env-wrong';
    }

    public function testLoaderSkipsCommentsAndEmptyLines()
    {
        $file = new Loader($this->fixturesFolder . DIRECTORY_SEPARATOR . 'commented.env');
        $result = '';
        $file->next(function ($line) use (&$result) {
            $result[] = $line;
        });

        $this->assertCount(5, $result);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testLoaderThrowsExceptionIfFileNotReadable()
    {
        $file = new Loader($this->fixturesFolder . DIRECTORY_SEPARATOR . 'file_does_not_exists.env');

        // try to read file
        $file->next(function ($line) use (&$result) {
            $result[] = $line;
        });
    }
}
