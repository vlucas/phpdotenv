<?php

use Dotenv\Dotenv;

class ValidatorBooleanTest extends PHPUnit_Framework_TestCase
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
     * List of valid boolean values in fixtures/env/booleans.env
     *
     * @return array
     */
    public function validBooleanValuesDataProvider()
    {
        return array(
            array('VALID_EXPLICIT_LOWERCASE_TRUE'),
            array('VALID_EXPLICIT_LOWERCASE_FALSE'),
            array('VALID_EXPLICIT_UPPERCASE_TRUE'),
            array('VALID_EXPLICIT_UPPERCASE_FALSE'),
            array('VALID_EXPLICIT_MIXEDCASE_TRUE'),
            array('VALID_EXPLICIT_MIXEDCASE_FALSE'),

            array('VALID_NUMBER_TRUE'),
            array('VALID_NUMBER_FALSE'),

            array('VALID_ONOFF_LOWERCASE_TRUE'),
            array('VALID_ONOFF_LOWERCASE_FALSE'),
            array('VALID_ONOFF_UPPERCASE_TRUE'),
            array('VALID_ONOFF_UPPERCASE_FALSE'),
            array('VALID_ONOFF_MIXEDCASE_TRUE'),
            array('VALID_ONOFF_MIXEDCASE_FALSE'),

            array('VALID_YESNO_LOWERCASE_TRUE'),
            array('VALID_YESNO_LOWERCASE_FALSE'),
            array('VALID_YESNO_UPPERCASE_TRUE'),
            array('VALID_YESNO_UPPERCASE_FALSE'),
            array('VALID_YESNO_MIXEDCASE_TRUE'),
            array('VALID_YESNO_MIXEDCASE_FALSE'),
        );
    }

    /**
     * @dataProvider validBooleanValuesDataProvider
     */
    public function testCanValidateBooleans($boolean)
    {
        $dotenv = new Dotenv($this->fixturesFolder, 'booleans.env');
        $dotenv->load();

        $dotenv->required($boolean)->isBoolean();

        $this->assertTrue(true); // anything wrong - an exception will be thrown
    }

    /**
     * List of non-boolean values in fixtures/env/booleans.env
     *
     * @return array
     */
    public function invalidBooleanValuesDataProvider()
    {
        return array(
            array('INVALID_SOMETHING'),
            array('INVALID_EMPTY'),
            array('INVALID_EMPTY_STRING'),
            array('INVALID_NULL'),
            array('INVALID_NUMBER_POSITIVE'),
            array('INVALID_NUMBER_NEGATIVE'),
            array('INVALID_MINUS'),
            array('INVALID_TILDA'),
            array('INVALID_EXCLAMATION'),
        );
    }

    /**
     * @dataProvider invalidBooleanValuesDataProvider
     * @expectedException Dotenv\Exception\ValidationException
     */
    public function testCanInvalidateNonBooleans($boolean)
    {
        $dotenv = new Dotenv($this->fixturesFolder, 'booleans.env');
        $dotenv->load();

        $dotenv->required($boolean)->isBoolean();
    }
}
