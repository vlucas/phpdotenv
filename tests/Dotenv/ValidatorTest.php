<?php

namespace Dotenv\Tests;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    /**
     * @var string
     */
    private $fixturesFolder;

    public function setUp()
    {
        $this->fixturesFolder = dirname(__DIR__).'/fixtures/env';
    }

    public function testDotenvRequiredStringEnvironmentVars()
    {
        $dotenv = Dotenv::createImmutable($this->fixturesFolder);
        $dotenv->load();
        $dotenv->required('FOO');
        self::assertTrue(true);
    }

    public function testDotenvAllowedValues()
    {
        $dotenv = Dotenv::createImmutable($this->fixturesFolder);
        $dotenv->load();
        $dotenv->required('FOO')->allowedValues(['bar', 'baz']);
        self::assertTrue(true);
    }

    public function testDotenvAllowedValuesIfPresent()
    {
        $dotenv = Dotenv::createImmutable($this->fixturesFolder);
        $dotenv->load();
        $dotenv->ifPresent('FOO')->allowedValues(['bar', 'baz']);
        self::assertTrue(true);
    }

    public function testDotenvAllowedValuesIfNotPresent()
    {
        $dotenv = Dotenv::createImmutable($this->fixturesFolder);
        $dotenv->load();
        $dotenv->ifPresent('FOOQWERTYOOOOOO')->allowedValues(['bar', 'baz']);
        self::assertTrue(true);
    }

    /**
     * @expectedException \Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: FOO is not one of [buzz, buz].
     */
    public function testDotenvProhibitedValues()
    {
        $dotenv = Dotenv::createImmutable($this->fixturesFolder);
        $dotenv->load();
        $dotenv->required('FOO')->allowedValues(['buzz', 'buz']);
    }

    /**
     * @expectedException \Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: FOO is not one of [buzz, buz].
     */
    public function testDotenvProhibitedValuesIfPresent()
    {
        $dotenv = Dotenv::createImmutable($this->fixturesFolder);
        $dotenv->load();
        $dotenv->ifPresent('FOO')->allowedValues(['buzz', 'buz']);
    }

    /**
     * @expectedException \Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: FOOX is missing, NOPE is missing.
     */
    public function testDotenvRequiredThrowsRuntimeException()
    {
        $dotenv = Dotenv::createImmutable($this->fixturesFolder);
        $dotenv->load();
        self::assertFalse(getenv('FOOX'));
        self::assertFalse(getenv('NOPE'));
        $dotenv->required(['FOOX', 'NOPE']);
    }

    public function testDotenvRequiredArrayEnvironmentVars()
    {
        $dotenv = Dotenv::createImmutable($this->fixturesFolder);
        $dotenv->load();
        $dotenv->required(['FOO', 'BAR']);
        self::assertTrue(true);
    }

    public function testDotenvAssertions()
    {
        $dotenv = Dotenv::createImmutable($this->fixturesFolder, 'assertions.env');
        $dotenv->load();
        self::assertSame('val1', getenv('ASSERTVAR1'));
        self::assertEmpty(getenv('ASSERTVAR2'));
        self::assertSame('val3   ', getenv('ASSERTVAR3'));
        self::assertSame('0', getenv('ASSERTVAR4'));
        self::assertSame('#foo', getenv('ASSERTVAR5'));
        self::assertSame("val1\nval2", getenv('ASSERTVAR6'));
        self::assertSame("\nval3", getenv('ASSERTVAR7'));
        self::assertSame("val3\n", getenv('ASSERTVAR8'));

        $dotenv->required([
            'ASSERTVAR1',
            'ASSERTVAR2',
            'ASSERTVAR3',
            'ASSERTVAR4',
            'ASSERTVAR5',
            'ASSERTVAR6',
            'ASSERTVAR7',
            'ASSERTVAR8',
            'ASSERTVAR9',
        ]);

        $dotenv->required([
            'ASSERTVAR1',
            'ASSERTVAR3',
            'ASSERTVAR4',
            'ASSERTVAR5',
            'ASSERTVAR6',
            'ASSERTVAR7',
            'ASSERTVAR8',
        ])->notEmpty();

        $dotenv->required([
            'ASSERTVAR1',
            'ASSERTVAR4',
            'ASSERTVAR5',
        ])->notEmpty()->allowedValues(['0', 'val1', '#foo']);
    }

    /**
     * @expectedException \Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: ASSERTVAR2 is empty.
     */
    public function testDotenvEmptyThrowsRuntimeException()
    {
        $dotenv = Dotenv::createImmutable($this->fixturesFolder, 'assertions.env');
        $dotenv->load();
        self::assertEmpty(getenv('ASSERTVAR2'));

        $dotenv->required('ASSERTVAR2')->notEmpty();
    }

    public function testDotenvEmptyWhenNotPresent()
    {
        $dotenv = Dotenv::createImmutable($this->fixturesFolder, 'assertions.env');
        $dotenv->load();

        $dotenv->ifPresent('ASSERTVAR2_NO_SUCH_VARIABLE')->notEmpty();
        self::assertTrue(true);
    }

    /**
     * @expectedException \Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: ASSERTVAR9 is empty.
     */
    public function testDotenvStringOfSpacesConsideredEmpty()
    {
        $dotenv = Dotenv::createImmutable($this->fixturesFolder, 'assertions.env');
        $dotenv->load();
        $dotenv->required('ASSERTVAR9')->notEmpty();
    }

    /**
     * @expectedException \Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: foo is missing.
     */
    public function testDotenvValidateRequiredWithoutLoading()
    {
        $dotenv = Dotenv::createImmutable($this->fixturesFolder, 'assertions.env');
        $dotenv->required('foo');
    }

    public function testDotenvRequiredCanBeUsedWithoutLoadingFile()
    {
        putenv('REQUIRED_VAR=1');
        $dotenv = Dotenv::createImmutable($this->fixturesFolder);
        $dotenv->required('REQUIRED_VAR')->notEmpty();
        self::assertTrue(true);
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
        $dotenv = Dotenv::createImmutable($this->fixturesFolder, 'booleans.env');
        $dotenv->load();

        $dotenv->required($boolean)->isBoolean();
        self::assertTrue(true);
    }

    /**
     * @dataProvider validBooleanValuesDataProvider
     */
    public function testCanValidateBooleansIfPresent($boolean)
    {
        $dotenv = Dotenv::createImmutable($this->fixturesFolder, 'booleans.env');
        $dotenv->load();

        $dotenv->ifPresent($boolean)->isBoolean();
        self::assertTrue(true);
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
     * @expectedException \Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: INVALID_
     */
    public function testCanInvalidateNonBooleans($boolean)
    {
        $dotenv = Dotenv::createImmutable($this->fixturesFolder, 'booleans.env');
        $dotenv->load();

        $dotenv->required($boolean)->isBoolean();
    }

    /**
     * @dataProvider invalidBooleanValuesDataProvider
     * @expectedException \Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: INVALID_
     */
    public function testCanInvalidateNonBooleansIfPresent($boolean)
    {
        $dotenv = Dotenv::createImmutable($this->fixturesFolder, 'booleans.env');
        $dotenv->load();

        $dotenv->ifPresent($boolean)->isBoolean();
    }

    /**
     * @expectedException \Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: VAR_DOES_NOT_EXIST_234782462764
     */
    public function testCanInvalidateBooleanNonExist()
    {
        $dotenv = Dotenv::createImmutable($this->fixturesFolder, 'booleans.env');
        $dotenv->load();

        $dotenv->required(['VAR_DOES_NOT_EXIST_234782462764'])->isBoolean();
    }

    public function testIfPresentBooleanNonExist()
    {
        $dotenv = Dotenv::createImmutable($this->fixturesFolder, 'booleans.env');
        $dotenv->load();

        $dotenv->ifPresent(['VAR_DOES_NOT_EXIST_234782462764'])->isBoolean();
        self::assertTrue(true);
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
        $dotenv = Dotenv::createImmutable($this->fixturesFolder, 'integers.env');
        $dotenv->load();

        $dotenv->required($integer)->isInteger();
        self::assertTrue(true);
    }

    /**
     * @dataProvider validIntegerValuesDataProvider
     */
    public function testCanValidateIntegersIfPresent($integer)
    {
        $dotenv = Dotenv::createImmutable($this->fixturesFolder, 'integers.env');
        $dotenv->load();

        $dotenv->ifPresent($integer)->isInteger();
        self::assertTrue(true);
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
        $dotenv = Dotenv::createImmutable($this->fixturesFolder, 'integers.env');
        $dotenv->load();

        $dotenv->required($integer)->isInteger();
    }

    /**
     * @dataProvider invalidIntegerValuesDataProvider
     * @expectedException \Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: INVALID_
     */
    public function testCanInvalidateNonIntegersIfExist($integer)
    {
        $dotenv = Dotenv::createImmutable($this->fixturesFolder, 'integers.env');
        $dotenv->load();

        $dotenv->ifPresent($integer)->isInteger();
    }

    /**
     * @expectedException \Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: VAR_DOES_NOT_EXIST_234782462764
     */
    public function testCanInvalidateIntegerNonExist()
    {
        $dotenv = Dotenv::createImmutable($this->fixturesFolder, 'integers.env');
        $dotenv->load();

        $dotenv->required(['VAR_DOES_NOT_EXIST_234782462764'])->isInteger();
    }

    public function testIfPresentIntegerNonExist()
    {
        $dotenv = Dotenv::createImmutable($this->fixturesFolder, 'integers.env');
        $dotenv->load();

        $dotenv->ifPresent(['VAR_DOES_NOT_EXIST_234782462764'])->isInteger();
        self::assertTrue(true);
    }

    public function testDotenvRegexMatchPass()
    {
        $dotenv = Dotenv::createImmutable($this->fixturesFolder);
        $dotenv->load();
        $dotenv->required('FOO')->allowedRegexValues('([[:lower:]]{3})');
        self::assertTrue(true);
    }

    /**
     * @expectedException \Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: FOO does not match "/^([[:lower:]]{1})$/".
     */
    public function testDotenvRegexMatchFail()
    {
        $dotenv = Dotenv::createImmutable($this->fixturesFolder);
        $dotenv->load();
        $dotenv->required('FOO')->allowedRegexValues('/^([[:lower:]]{1})$/');
    }

    /**
     * @expectedException \Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: FOO does not match "/([[:lower:]{1{".
     */
    public function testDotenvRegexMatchError()
    {
        $dotenv = Dotenv::createImmutable($this->fixturesFolder);
        $dotenv->load();
        $dotenv->required('FOO')->allowedRegexValues('/([[:lower:]{1{');
    }

    public function testDotenvRegexMatchNotPresent()
    {
        $dotenv = Dotenv::createImmutable($this->fixturesFolder);
        $dotenv->load();
        $dotenv->ifPresent('FOOOOOOOOOOO')->allowedRegexValues('([[:lower:]]{3})');
        self::assertTrue(true);
    }
}
