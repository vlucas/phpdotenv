<?php

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;

class ValidatorBooleanTest extends TestCase
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
     * List of valid boolean values in fixtures/env/booleans.env.
     *
     * @return array
     */
    public function validBooleanValuesDataProvider()
    {
        return [
            ['VALID_EXPLICIT_LOWERCASE_TRUE'],
            ['VALID_EXPLICIT_LOWERCASE_FALSE'],
            ['VALID_EXPLICIT_UPPERCASE_TRUE'],
            ['VALID_EXPLICIT_UPPERCASE_FALSE'],
            ['VALID_EXPLICIT_MIXEDCASE_TRUE'],
            ['VALID_EXPLICIT_MIXEDCASE_FALSE'],

            ['VALID_NUMBER_TRUE'],
            ['VALID_NUMBER_FALSE'],

            ['VALID_ONOFF_LOWERCASE_TRUE'],
            ['VALID_ONOFF_LOWERCASE_FALSE'],
            ['VALID_ONOFF_UPPERCASE_TRUE'],
            ['VALID_ONOFF_UPPERCASE_FALSE'],
            ['VALID_ONOFF_MIXEDCASE_TRUE'],
            ['VALID_ONOFF_MIXEDCASE_FALSE'],

            ['VALID_YESNO_LOWERCASE_TRUE'],
            ['VALID_YESNO_LOWERCASE_FALSE'],
            ['VALID_YESNO_UPPERCASE_TRUE'],
            ['VALID_YESNO_UPPERCASE_FALSE'],
            ['VALID_YESNO_MIXEDCASE_TRUE'],
            ['VALID_YESNO_MIXEDCASE_FALSE'],
        ];
    }

    /**
     * @dataProvider validBooleanValuesDataProvider
     */
    public function testCanValidateBooleans($boolean)
    {
        $dotenv = Dotenv::create($this->fixturesFolder, 'booleans.env');
        $dotenv->load();

        $dotenv->required($boolean)->isBoolean();

        $this->assertTrue(true); // anything wrong - an exception will be thrown
    }

    /**
     * List of non-boolean values in fixtures/env/booleans.env.
     *
     * @return array
     */
    public function invalidBooleanValuesDataProvider()
    {
        return [
            ['INVALID_SOMETHING'],
            ['INVALID_EMPTY'],
            ['INVALID_EMPTY_STRING'],
            ['INVALID_NULL'],
            ['INVALID_NUMBER_POSITIVE'],
            ['INVALID_NUMBER_NEGATIVE'],
            ['INVALID_MINUS'],
            ['INVALID_TILDA'],
            ['INVALID_EXCLAMATION'],
        ];
    }

    /**
     * @dataProvider invalidBooleanValuesDataProvider
     * @expectedException Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: INVALID_
     */
    public function testCanInvalidateNonBooleans($boolean)
    {
        $dotenv = Dotenv::create($this->fixturesFolder, 'booleans.env');
        $dotenv->load();

        $dotenv->required($boolean)->isBoolean();
    }
}
