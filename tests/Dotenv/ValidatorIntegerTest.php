<?php

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;

class ValidatorIntegerTest extends TestCase
{
    /**
     * @var string
     */
    private $fixturesFolder;

    public function setUp()
    {
        $this->fixturesFolder = dirname(__DIR__).'/fixtures/env';
    }

    /**
     * List of valid integer values in fixtures/env/integers.env.
     *
     * @return array
     */
    public function validIntegerValuesDataProvider()
    {
        return [
            ['VALID_ZERO'],
            ['VALID_ONE'],
            ['VALID_TWO'],

            ['VALID_LARGE'],
            ['VALID_HUGE'],
        ];
    }

    /**
     * @dataProvider validIntegerValuesDataProvider
     */
    public function testCanValidateIntegers($integer)
    {
        $dotenv = Dotenv::create($this->fixturesFolder, 'integers.env');
        $dotenv->load();

        $dotenv->required($integer)->isInteger();

        $this->assertTrue(true); // anything wrong - an exception will be thrown
    }

    /**
     * List of non-integer values in fixtures/env/integers.env.
     *
     * @return array
     */
    public function invalidIntegerValuesDataProvider()
    {
        return [
            ['INVALID_SOMETHING'],
            ['INVALID_EMPTY'],
            ['INVALID_EMPTY_STRING'],
            ['INVALID_NULL'],
            ['INVALID_NEGATIVE'],
            ['INVALID_MINUS'],
            ['INVALID_TILDA'],
            ['INVALID_EXCLAMATION'],
            ['INVALID_SPACES'],
            ['INVALID_COMMAS'],
        ];
    }

    /**
     * @dataProvider invalidIntegerValuesDataProvider
     * @expectedException \Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: INVALID_
     */
    public function testCanInvalidateNonIntegers($integer)
    {
        $dotenv = Dotenv::create($this->fixturesFolder, 'integers.env');
        $dotenv->load();

        $dotenv->required($integer)->isInteger();
    }
}
