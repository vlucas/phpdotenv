<?php

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;

class DotenvTest extends TestCase
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
     * @expectedException \Dotenv\Exception\InvalidPathException
     * @expectedExceptionMessage Unable to read the environment file at
     */
    public function testDotenvThrowsExceptionIfUnableToLoadFile()
    {
        $dotenv = new Dotenv(__DIR__);
        $dotenv->load();
    }

    public function testDotenvSkipsLoadingIfFileIsMissing()
    {
        $dotenv = new Dotenv(__DIR__);
        $this->assertEmpty($dotenv->safeLoad());
    }

    public function testDotenvLoadsEnvironmentVars()
    {
        $dotenv = new Dotenv($this->fixturesFolder);
        $dotenv->load();
        $this->assertSame('bar', getenv('FOO'));
        $this->assertSame('baz', getenv('BAR'));
        $this->assertSame('with spaces', getenv('SPACED'));
        $this->assertEmpty(getenv('NULL'));
    }

    public function testCommentedDotenvLoadsEnvironmentVars()
    {
        $dotenv = new Dotenv($this->fixturesFolder, 'commented.env');
        $dotenv->load();
        $this->assertSame('bar', getenv('CFOO'));
        $this->assertFalse(getenv('CBAR'));
        $this->assertFalse(getenv('CZOO'));
        $this->assertSame('with spaces', getenv('CSPACED'));
        $this->assertSame('a value with a # character', getenv('CQUOTES'));
        $this->assertSame('a value with a # character & a quote " character inside quotes', getenv('CQUOTESWITHQUOTE'));
        $this->assertEmpty(getenv('CNULL'));
        $this->assertEmpty(getenv('EMPTY'));
    }

    public function testQuotedDotenvLoadsEnvironmentVars()
    {
        $dotenv = new Dotenv($this->fixturesFolder, 'quoted.env');
        $dotenv->load();
        $this->assertSame('bar', getenv('QFOO'));
        $this->assertSame('baz', getenv('QBAR'));
        $this->assertSame('with spaces', getenv('QSPACED'));
        $this->assertEmpty(getenv('QNULL'));
        $this->assertSame('pgsql:host=localhost;dbname=test', getenv('QEQUALS'));

        $this->assertSame('test some escaped characters like a quote (") or maybe a backslash (\\)', getenv('QESCAPED'));
        $this->assertSame('iiiiviiiixiiiiviiii\\n', getenv('QSLASH1'));
        $this->assertSame('iiiiviiiixiiiiviiii\\n', getenv('QSLASH2'));

        $this->assertSame('test some escaped characters like a quote (\') or maybe a backslash (\\)', getenv('SQESCAPED'));
        $this->assertSame('iiiiviiiixiiiiviiii\\n', getenv('SQSLASH1'));
        $this->assertSame('iiiiviiiixiiiiviiii\\n', getenv('SQSLASH2'));
    }

    public function testLargeDotenvLoadsEnvironmentVars()
    {
        $dotenv = new Dotenv($this->fixturesFolder, 'large.env');
        $dotenv->load();
        $this->assertNotEmpty(getenv('LARGE'));
    }

    /**
     * @expectedException \Dotenv\Exception\InvalidFileException
     * @expectedExceptionMessage Dotenv values containing spaces must be surrounded by quotes.
     */
    public function testSpacedValuesWithoutQuotesThrowsException()
    {
        $dotenv = new Dotenv(dirname(__DIR__).'/fixtures/env-wrong', 'spaced-wrong.env');
        $dotenv->load();
    }

    public function testExportedDotenvLoadsEnvironmentVars()
    {
        $dotenv = new Dotenv($this->fixturesFolder, 'exported.env');
        $dotenv->load();
        $this->assertSame('bar', getenv('EFOO'));
        $this->assertSame('baz', getenv('EBAR'));
        $this->assertSame('with spaces', getenv('ESPACED'));
        $this->assertEmpty(getenv('ENULL'));
    }

    public function testDotenvLoadsEnvGlobals()
    {
        $dotenv = new Dotenv($this->fixturesFolder);
        $dotenv->load();
        $this->assertSame('bar', $_SERVER['FOO']);
        $this->assertSame('baz', $_SERVER['BAR']);
        $this->assertSame('with spaces', $_SERVER['SPACED']);
        $this->assertEmpty($_SERVER['NULL']);
    }

    public function testDotenvLoadsServerGlobals()
    {
        $dotenv = new Dotenv($this->fixturesFolder);
        $dotenv->load();
        $this->assertSame('bar', $_ENV['FOO']);
        $this->assertSame('baz', $_ENV['BAR']);
        $this->assertSame('with spaces', $_ENV['SPACED']);
        $this->assertEmpty($_ENV['NULL']);
    }

    /**
     * @depends testDotenvLoadsEnvironmentVars
     * @depends testDotenvLoadsEnvGlobals
     * @depends testDotenvLoadsServerGlobals
     */
    public function testDotenvRequiredStringEnvironmentVars()
    {
        $dotenv = new Dotenv($this->fixturesFolder);
        $dotenv->load();
        $dotenv->required('FOO');
        $this->assertTrue(true); // anything wrong an exception will be thrown
    }

    /**
     * @depends testDotenvLoadsEnvironmentVars
     * @depends testDotenvLoadsEnvGlobals
     * @depends testDotenvLoadsServerGlobals
     */
    public function testDotenvRequiredArrayEnvironmentVars()
    {
        $dotenv = new Dotenv($this->fixturesFolder);
        $dotenv->load();
        $dotenv->required(array('FOO', 'BAR'));
        $this->assertTrue(true); // anything wrong an exception will be thrown
    }

    public function testDotenvNestedEnvironmentVars()
    {
        $dotenv = new Dotenv($this->fixturesFolder, 'nested.env');
        $dotenv->load();
        $this->assertSame('{$NVAR1} {$NVAR2}', $_ENV['NVAR3']); // not resolved
        $this->assertSame('Hello World!', $_ENV['NVAR4']);
        $this->assertSame('$NVAR1 {NVAR2}', $_ENV['NVAR5']); // not resolved
        $this->assertSame('Special Value', $_ENV['N.VAR6']); // new '.' (dot) in var name
        $this->assertSame('Special Value', $_ENV['NVAR7']);  // nested '.' (dot) variable
    }

    /**
     * @depends testDotenvLoadsEnvironmentVars
     * @depends testDotenvLoadsEnvGlobals
     * @depends testDotenvLoadsServerGlobals
     */
    public function testDotenvAllowedValues()
    {
        $dotenv = new Dotenv($this->fixturesFolder);
        $dotenv->load();
        $dotenv->required('FOO')->allowedValues(array('bar', 'baz'));
        $this->assertTrue(true); // anything wrong an exception will be thrown
    }

    /**
     * @depends testDotenvLoadsEnvironmentVars
     * @depends testDotenvLoadsEnvGlobals
     * @depends testDotenvLoadsServerGlobals
     *
     * @expectedException \Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: FOO is not an allowed value.
     */
    public function testDotenvProhibitedValues()
    {
        $dotenv = new Dotenv($this->fixturesFolder);
        $dotenv->load();
        $dotenv->required('FOO')->allowedValues(array('buzz'));
    }

    /**
     * @expectedException \Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: FOOX is missing, NOPE is missing.
     */
    public function testDotenvRequiredThrowsRuntimeException()
    {
        $dotenv = new Dotenv($this->fixturesFolder);
        $dotenv->load();
        $this->assertFalse(getenv('FOOX'));
        $this->assertFalse(getenv('NOPE'));
        $dotenv->required(array('FOOX', 'NOPE'));
    }

    public function testDotenvNullFileArgumentUsesDefault()
    {
        $dotenv = new Dotenv($this->fixturesFolder, null);
        $dotenv->load();
        $this->assertSame('bar', getenv('FOO'));
    }

    /**
     * The fixture data has whitespace between the key and in the value string.
     *
     * Test that these keys are trimmed down.
     */
    public function testDotenvTrimmedKeys()
    {
        $dotenv = new Dotenv($this->fixturesFolder, 'quoted.env');
        $dotenv->load();
        $this->assertSame('no space', getenv('QWHITESPACE'));
    }

    public function testDotenvLoadDoesNotOverwriteEnv()
    {
        putenv('IMMUTABLE=true');
        $dotenv = new Dotenv($this->fixturesFolder, 'immutable.env');
        $dotenv->load();
        $this->assertSame('true', getenv('IMMUTABLE'));
    }

    public function testDotenvLoadAfterOverload()
    {
        putenv('IMMUTABLE=true');
        $dotenv = new Dotenv($this->fixturesFolder, 'immutable.env');
        $dotenv->overload();
        $this->assertSame('false', getenv('IMMUTABLE'));

        putenv('IMMUTABLE=true');
        $dotenv->load();
        $this->assertSame('true', getenv('IMMUTABLE'));
    }

    public function testDotenvOverloadAfterLoad()
    {
        putenv('IMMUTABLE=true');
        $dotenv = new Dotenv($this->fixturesFolder, 'immutable.env');
        $dotenv->load();
        $this->assertSame('true', getenv('IMMUTABLE'));

        putenv('IMMUTABLE=true');
        $dotenv->overload();
        $this->assertSame('false', getenv('IMMUTABLE'));
    }

    public function testDotenvOverloadDoesOverwriteEnv()
    {
        $dotenv = new Dotenv($this->fixturesFolder, 'mutable.env');
        $dotenv->overload();
        $this->assertSame('true', getenv('MUTABLE'));
    }

    public function testDotenvAllowsSpecialCharacters()
    {
        $dotenv = new Dotenv($this->fixturesFolder, 'specialchars.env');
        $dotenv->load();
        $this->assertSame('$a6^C7k%zs+e^.jvjXk', getenv('SPVAR1'));
        $this->assertSame('?BUty3koaV3%GA*hMAwH}B', getenv('SPVAR2'));
        $this->assertSame('jdgEB4{QgEC]HL))&GcXxokB+wqoN+j>xkV7K?m$r', getenv('SPVAR3'));
        $this->assertSame('22222:22#2^{', getenv('SPVAR4'));
        $this->assertSame('test some escaped characters like a quote " or maybe a backslash \\', getenv('SPVAR5'));
        $this->assertSame('secret!@#', getenv('SPVAR6'));
        $this->assertSame('secret!@#', getenv('SPVAR7'));
        $this->assertSame('secret!@#', getenv('SPVAR8'));
    }

    public function testDotenvAssertions()
    {
        $dotenv = new Dotenv($this->fixturesFolder, 'assertions.env');
        $dotenv->load();
        $this->assertSame('val1', getenv('ASSERTVAR1'));
        $this->assertEmpty(getenv('ASSERTVAR2'));
        $this->assertEmpty(getenv('ASSERTVAR3'));
        $this->assertSame('0', getenv('ASSERTVAR4'));
        $this->assertSame('#foo', getenv('ASSERTVAR5'));

        $dotenv->required(array(
            'ASSERTVAR1',
            'ASSERTVAR2',
            'ASSERTVAR3',
            'ASSERTVAR4',
            'ASSERTVAR5',
        ));

        $dotenv->required(array(
            'ASSERTVAR1',
            'ASSERTVAR4',
            'ASSERTVAR5',
        ))->notEmpty();

        $dotenv->required(array(
            'ASSERTVAR1',
            'ASSERTVAR4',
            'ASSERTVAR5',
        ))->notEmpty()->allowedValues(array('0', 'val1', '#foo'));

        $this->assertTrue(true); // anything wrong an an exception will be thrown
    }

    /**
     * @expectedException \Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: ASSERTVAR2 is empty.
     */
    public function testDotenvEmptyThrowsRuntimeException()
    {
        $dotenv = new Dotenv($this->fixturesFolder, 'assertions.env');
        $dotenv->load();
        $this->assertEmpty(getenv('ASSERTVAR2'));

        $dotenv->required('ASSERTVAR2')->notEmpty();
    }

    /**
     * @expectedException \Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: ASSERTVAR3 is empty.
     */
    public function testDotenvStringOfSpacesConsideredEmpty()
    {
        $dotenv = new Dotenv($this->fixturesFolder, 'assertions.env');
        $dotenv->load();
        $this->assertEmpty(getenv('ASSERTVAR3'));

        $dotenv->required('ASSERTVAR3')->notEmpty();
    }

    /**
     * @expectedException \Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: ASSERTVAR3 is empty.
     */
    public function testDotenvHitsLastChain()
    {
        $dotenv = new Dotenv($this->fixturesFolder, 'assertions.env');
        $dotenv->load();
        $dotenv->required('ASSERTVAR3')->notEmpty();
    }

    /**
     * @expectedException \Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: foo is missing.
     */
    public function testDotenvValidateRequiredWithoutLoading()
    {
        $dotenv = new Dotenv($this->fixturesFolder, 'assertions.env');
        $dotenv->required('foo');
    }

    public function testDotenvRequiredCanBeUsedWithoutLoadingFile()
    {
        putenv('REQUIRED_VAR=1');
        $dotenv = new Dotenv($this->fixturesFolder);
        $dotenv->required('REQUIRED_VAR')->notEmpty();
        $this->assertTrue(true);
    }

    public function testGetEnvironmentVariablesList()
    {
        $dotenv = new Dotenv($this->fixturesFolder);
        $dotenv->load();
        $this->assertTrue(is_array($dotenv->getEnvironmentVariableNames()));
        $this->assertSame(array('FOO', 'BAR', 'SPACED', 'NULL'), $dotenv->getEnvironmentVariableNames());
    }
}
