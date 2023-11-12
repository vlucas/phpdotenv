<?php

declare(strict_types=1);

namespace Dotenv\Tests;

use Dotenv\Dotenv;
use Dotenv\Exception\ValidationException;
use Dotenv\Repository\Adapter\ArrayAdapter;
use Dotenv\Repository\RepositoryBuilder;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
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
        self::$folder = \dirname(__DIR__).'/fixtures/env';
    }

    /**
     * @param string $name
     *
     * @return array{\Dotenv\Repository\RepositoryInterface,\Dotenv\Dotenv}
     */
    public static function createArrayDotenv(string $name = '.env')
    {
        $repository = RepositoryBuilder::createWithNoAdapters()->addAdapter(ArrayAdapter::class)->make();

        return [$repository, Dotenv::create($repository, self::$folder, $name)];
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testDotenvRequiredStringEnvironmentVars()
    {
        $dotenv = self::createArrayDotenv()[1];
        $dotenv->load();
        $dotenv->required('FOO');
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testDotenvAllowedValues()
    {
        $dotenv = self::createArrayDotenv()[1];
        $dotenv->load();
        $dotenv->required('FOO')->allowedValues(['bar', 'baz']);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testDotenvAllowedValuesIfPresent()
    {
        $dotenv = self::createArrayDotenv()[1];
        $dotenv->load();
        $dotenv->ifPresent('FOO')->allowedValues(['bar', 'baz']);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testDotenvAllowedValuesIfNotPresent()
    {
        $dotenv = self::createArrayDotenv()[1];
        $dotenv->load();
        $dotenv->ifPresent('FOOQWERTYOOOOOO')->allowedValues(['bar', 'baz']);
    }

    public function testDotenvProhibitedValues()
    {
        $dotenv = self::createArrayDotenv()[1];
        $dotenv->load();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('One or more environment variables failed assertions: FOO is not one of [buzz, buz].');

        $dotenv->required('FOO')->allowedValues(['buzz', 'buz']);
    }

    public function testDotenvProhibitedValuesIfPresent()
    {
        $dotenv = self::createArrayDotenv()[1];
        $dotenv->load();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('One or more environment variables failed assertions: FOO is not one of [buzz, buz].');

        $dotenv->ifPresent('FOO')->allowedValues(['buzz', 'buz']);
    }

    public function testDotenvRequiredThrowsRuntimeException()
    {
        [$repo, $dotenv] = self::createArrayDotenv();

        $dotenv->load();

        self::assertFalse($repo->has('FOOX'));
        self::assertFalse($repo->has('NOPE'));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('One or more environment variables failed assertions: FOOX is missing, NOPE is missing.');

        $dotenv->required(['FOOX', 'NOPE']);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testDotenvRequiredArrayEnvironmentVars()
    {
        $dotenv = self::createArrayDotenv()[1];
        $dotenv->load();
        $dotenv->required(['FOO', 'BAR']);
    }

    public function testDotenvAssertions()
    {
        [$repo, $dotenv] = self::createArrayDotenv('assertions.env');

        $dotenv->load();

        self::assertSame('val1', $repo->get('ASSERTVAR1'));
        self::assertSame('', $repo->get('ASSERTVAR2'));
        self::assertSame('val3   ', $repo->get('ASSERTVAR3'));
        self::assertSame('0', $repo->get('ASSERTVAR4'));
        self::assertSame('#foo', $repo->get('ASSERTVAR5'));
        self::assertSame("val1\nval2", $repo->get('ASSERTVAR6'));
        self::assertSame("\nval3", $repo->get('ASSERTVAR7'));
        self::assertSame("val3\n", $repo->get('ASSERTVAR8'));

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

    public function testDotenvEmptyThrowsRuntimeException()
    {
        $dotenv = self::createArrayDotenv('assertions.env')[1];
        $dotenv->load();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('One or more environment variables failed assertions: ASSERTVAR2 is empty.');

        $dotenv->required('ASSERTVAR2')->notEmpty();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testDotenvEmptyWhenNotPresent()
    {
        $dotenv = self::createArrayDotenv('assertions.env')[1];
        $dotenv->load();
        $dotenv->ifPresent('ASSERTVAR2_NO_SUCH_VARIABLE')->notEmpty();
    }

    public function testDotenvStringOfSpacesConsideredEmpty()
    {
        $dotenv = self::createArrayDotenv('assertions.env')[1];
        $dotenv->load();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('One or more environment variables failed assertions: ASSERTVAR9 is empty.');

        $dotenv->required('ASSERTVAR9')->notEmpty();
    }

    /**
     * List of valid boolean values in fixtures/env/booleans.env.
     *
     * @return string[][]
     */
    public static function validBooleanValuesDataProvider()
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
     * @doesNotPerformAssertions
     */
    public function testCanValidateBooleans(string $boolean)
    {
        $dotenv = Dotenv::createImmutable(self::$folder, 'booleans.env');
        $dotenv->load();
        $dotenv->required($boolean)->isBoolean();
    }

    /**
     * @dataProvider validBooleanValuesDataProvider
     * @doesNotPerformAssertions
     */
    public function testCanValidateBooleansIfPresent(string $boolean)
    {
        $dotenv = Dotenv::createImmutable(self::$folder, 'booleans.env');
        $dotenv->load();
        $dotenv->ifPresent($boolean)->isBoolean();
    }

    /**
     * List of non-boolean values in fixtures/env/booleans.env.
     *
     * @return string[][]
     */
    public static function invalidBooleanValuesDataProvider()
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
     */
    public function testCanInvalidateNonBooleans(string $boolean)
    {
        $dotenv = Dotenv::createImmutable(self::$folder, 'booleans.env');
        $dotenv->load();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('One or more environment variables failed assertions: INVALID_');

        $dotenv->required($boolean)->isBoolean();
    }

    /**
     * @dataProvider invalidBooleanValuesDataProvider
     */
    public function testCanInvalidateNonBooleansIfPresent(string $boolean)
    {
        $dotenv = Dotenv::createImmutable(self::$folder, 'booleans.env');
        $dotenv->load();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('One or more environment variables failed assertions: INVALID_');

        $dotenv->ifPresent($boolean)->isBoolean();
    }

    public function testCanInvalidateBooleanNonExist()
    {
        $dotenv = Dotenv::createImmutable(self::$folder, 'booleans.env');
        $dotenv->load();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('One or more environment variables failed assertions: VAR_DOES_NOT_EXIST_234782462764');

        $dotenv->required(['VAR_DOES_NOT_EXIST_234782462764'])->isBoolean();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testIfPresentBooleanNonExist()
    {
        $dotenv = Dotenv::createImmutable(self::$folder, 'booleans.env');
        $dotenv->load();
        $dotenv->ifPresent(['VAR_DOES_NOT_EXIST_234782462764'])->isBoolean();
    }

    /**
     * List of valid integer values in fixtures/env/integers.env.
     *
     * @return string[][]
     */
    public static function validIntegerValuesDataProvider()
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
     * @doesNotPerformAssertions
     */
    public function testCanValidateIntegers(string $integer)
    {
        $dotenv = Dotenv::createImmutable(self::$folder, 'integers.env');
        $dotenv->load();
        $dotenv->required($integer)->isInteger();
    }

    /**
     * @dataProvider validIntegerValuesDataProvider
     * @doesNotPerformAssertions
     */
    public function testCanValidateIntegersIfPresent(string $integer)
    {
        $dotenv = Dotenv::createImmutable(self::$folder, 'integers.env');
        $dotenv->load();
        $dotenv->ifPresent($integer)->isInteger();
    }

    /**
     * List of non-integer values in fixtures/env/integers.env.
     *
     * @return string[][]
     */
    public static function invalidIntegerValuesDataProvider()
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
     */
    public function testCanInvalidateNonIntegers(string $integer)
    {
        $dotenv = Dotenv::createImmutable(self::$folder, 'integers.env');
        $dotenv->load();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('One or more environment variables failed assertions: INVALID_');

        $dotenv->required($integer)->isInteger();
    }

    /**
     * @dataProvider invalidIntegerValuesDataProvider
     */
    public function testCanInvalidateNonIntegersIfExist(string $integer)
    {
        $dotenv = Dotenv::createImmutable(self::$folder, 'integers.env');
        $dotenv->load();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('One or more environment variables failed assertions: INVALID_');

        $dotenv->ifPresent($integer)->isInteger();
    }

    public function testCanInvalidateIntegerNonExist()
    {
        $dotenv = Dotenv::createImmutable(self::$folder, 'integers.env');
        $dotenv->load();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('One or more environment variables failed assertions: VAR_DOES_NOT_EXIST_234782462764');

        $dotenv->required(['VAR_DOES_NOT_EXIST_234782462764'])->isInteger();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testIfPresentIntegerNonExist()
    {
        $dotenv = Dotenv::createImmutable(self::$folder, 'integers.env');
        $dotenv->load();
        $dotenv->ifPresent(['VAR_DOES_NOT_EXIST_234782462764'])->isInteger();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testDotenvRegexMatchPass()
    {
        $dotenv = Dotenv::createImmutable(self::$folder);
        $dotenv->load();
        $dotenv->required('FOO')->allowedRegexValues('([[:lower:]]{3})');
    }

    public function testDotenvRegexMatchFail()
    {
        $dotenv = Dotenv::createImmutable(self::$folder);
        $dotenv->load();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('One or more environment variables failed assertions: FOO does not match "/^([[:lower:]]{1})$/".');

        $dotenv->required('FOO')->allowedRegexValues('/^([[:lower:]]{1})$/');
    }

    public function testDotenvRegexMatchError()
    {
        $dotenv = Dotenv::createImmutable(self::$folder);
        $dotenv->load();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('One or more environment variables failed assertions: FOO does not match "/([[:lower:]{1{".');

        $dotenv->required('FOO')->allowedRegexValues('/([[:lower:]{1{');
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testDotenvRegexMatchNotPresent()
    {
        $dotenv = Dotenv::createImmutable(self::$folder);
        $dotenv->load();
        $dotenv->ifPresent('FOOOOOOOOOOO')->allowedRegexValues('([[:lower:]]{3})');
    }
}
