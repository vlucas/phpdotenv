<?php

use Dotenv\Dotenv;

class DotenvTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->fixturesFolder = dirname(__DIR__).'/fixtures/env';
        $this->fixturesFolderWrong = dirname(__DIR__).'/fixtures/env-wrong';
    }

    public function testDotenvLoadsEnvironmentVars()
    {
        $dotenv = new Dotenv($this->fixturesFolder);
        $dotenv->load();
        $this->assertEquals('bar', getenv('FOO'));
        $this->assertEquals('baz', getenv('BAR'));
        $this->assertEquals('with spaces', getenv('SPACED'));
        $this->assertEquals('', getenv('NULL'));
    }

    public function testCommentedDotenvLoadsEnvironmentVars()
    {
        $dotenv = new Dotenv($this->fixturesFolder, 'commented.env');
        $dotenv->load();
        $this->assertEquals('bar', getenv('CFOO'));
        $this->assertEquals(false, getenv('CBAR'));
        $this->assertEquals(false, getenv('CZOO'));
        $this->assertEquals('with spaces', getenv('CSPACED'));
        $this->assertEquals('a value with a # character', getenv('CQUOTES'));
        $this->assertEquals('a value with a # character & a quote " character inside quotes', getenv('CQUOTESWITHQUOTE'));
        $this->assertEquals('', getenv('CNULL'));
    }

    public function testQuotedDotenvLoadsEnvironmentVars()
    {
        $dotenv = new Dotenv($this->fixturesFolder, 'quoted.env');
        $dotenv->load();
        $this->assertEquals('bar', getenv('QFOO'));
        $this->assertEquals('baz', getenv('QBAR'));
        $this->assertEquals('with spaces', getenv('QSPACED'));
        $this->assertEquals('', getenv('QNULL'));
        $this->assertEquals('pgsql:host=localhost;dbname=test', getenv('QEQUALS'));
        $this->assertEquals('test some escaped characters like a quote (") or maybe a backslash (\\)', getenv('QESCAPED'));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Dotenv values containing spaces must be surrounded by quotes.
     */
    public function testSpacedValuesWithoutQuotesThrowsException()
    {
        $dotenv = new Dotenv($this->fixturesFolderWrong, 'spaced-wrong.env');
        $dotenv->load();
    }

    public function testExportedDotenvLoadsEnvironmentVars()
    {
        $dotenv = new Dotenv($this->fixturesFolder, 'exported.env');
        $dotenv->load();
        $this->assertEquals('bar', getenv('EFOO'));
        $this->assertEquals('baz', getenv('EBAR'));
        $this->assertEquals('with spaces', getenv('ESPACED'));
        $this->assertEquals('', getenv('ENULL'));
    }

    public function testDotenvLoadsEnvGlobals()
    {
        $dotenv = new Dotenv($this->fixturesFolder);
        $dotenv->load();
        $this->assertEquals('bar', $_SERVER['FOO']);
        $this->assertEquals('baz', $_SERVER['BAR']);
        $this->assertEquals('with spaces', $_SERVER['SPACED']);
        $this->assertEquals('', $_SERVER['NULL']);
    }

    public function testDotenvLoadsServerGlobals()
    {
        $dotenv = new Dotenv($this->fixturesFolder);
        $dotenv->load();
        $this->assertEquals('bar', $_ENV['FOO']);
        $this->assertEquals('baz', $_ENV['BAR']);
        $this->assertEquals('with spaces', $_ENV['SPACED']);
        $this->assertEquals('', $_ENV['NULL']);
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
        $this->assertEquals('{$NVAR1} {$NVAR2}', $_ENV['NVAR3']); // not resolved
        $this->assertEquals('Hello World!', $_ENV['NVAR4']);
        $this->assertEquals('$NVAR1 {NVAR2}', $_ENV['NVAR5']); // not resolved
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
     * @expectedException RuntimeException
     * @expectedExceptionMessage One or more environment variables failed assertions: FOO is not an allowed value
     */
    public function testDotenvProhibitedValues()
    {
        $dotenv = new Dotenv($this->fixturesFolder);
        $dotenv->load();
        $dotenv->required('FOO')->allowedValues(array('buzz'));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage One or more environment variables failed assertions: FOOX is missing, NOPE is missing
     */
    public function testDotenvRequiredThrowsRuntimeException()
    {
        $dotenv = new Dotenv($this->fixturesFolder);
        $dotenv->load();
        $this->assertEquals(false, getenv('FOOX'));
        $this->assertEquals(false, getenv('NOPE'));
        $dotenv->required(array('FOOX', 'NOPE'));
    }

    public function testDotenvNullFileArgumentUsesDefault()
    {
        $dotenv = new Dotenv($this->fixturesFolder, null);
        $dotenv->load();
        $this->assertEquals('bar', getenv('FOO'));
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
        $this->assertEquals('no space', getenv('QWHITESPACE'));
    }

    public function testDotenvLoadDoesNotOverwriteEnv()
    {
        putenv('IMMUTABLE=true');
        $dotenv = new Dotenv($this->fixturesFolder, 'immutable.env');
        $dotenv->load();
        $this->assertEquals('true', getenv('IMMUTABLE'));
    }

    public function testDotenvOverloadDoesOverwriteEnv()
    {
        $dotenv = new Dotenv($this->fixturesFolder, 'mutable.env');
        $dotenv->overload();
        $this->assertEquals('true', getenv('MUTABLE'));
    }

    public function testDotenvAllowsSpecialCharacters()
    {
        $dotenv = new Dotenv($this->fixturesFolder, 'specialchars.env');
        $dotenv->load();
        $this->assertEquals('$a6^C7k%zs+e^.jvjXk', getenv('SPVAR1'));
        $this->assertEquals('?BUty3koaV3%GA*hMAwH}B', getenv('SPVAR2'));
        $this->assertEquals('jdgEB4{QgEC]HL))&GcXxokB+wqoN+j>xkV7K?m$r', getenv('SPVAR3'));
        $this->assertEquals('22222:22#2^{', getenv('SPVAR4'));
        $this->assertEquals('test some escaped characters like a quote " or maybe a backslash \\', getenv('SPVAR5'));
    }

    public function testDotenvAssertions()
    {
        $dotenv = new Dotenv($this->fixturesFolder, 'assertions.env');
        $dotenv->load();
        $this->assertEquals('val1', getenv('ASSERTVAR1'));
        $this->assertEquals('', getenv('ASSERTVAR2'));
        $this->assertEquals('', getenv('ASSERTVAR3'));
        $this->assertEquals('0', getenv('ASSERTVAR4'));

        $dotenv->required(array(
            'ASSERTVAR1',
            'ASSERTVAR2',
            'ASSERTVAR3',
            'ASSERTVAR4',
        ));

        $dotenv->required(array(
            'ASSERTVAR1',
            'ASSERTVAR4',
        ))->notEmpty();

        $dotenv->required(array(
            'ASSERTVAR1',
            'ASSERTVAR4',
        ))->notEmpty()->allowedValues(array('0', 'val1'));

        $this->assertTrue(true); // anything wrong an an exception will be thrown
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage One or more environment variables failed assertions: ASSERTVAR2 is empty
     */
    public function testDotenvEmptyThrowsRuntimeException()
    {
        $dotenv = new Dotenv($this->fixturesFolder, 'assertions.env');
        $dotenv->load();
        $this->assertEquals('', getenv('ASSERTVAR2'));

        $dotenv->required('ASSERTVAR2')->notEmpty();
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage One or more environment variables failed assertions: ASSERTVAR3 is empty
     */
    public function testDotenvStringOfSpacesConsideredEmpty()
    {
        $dotenv = new Dotenv($this->fixturesFolder, 'assertions.env');
        $dotenv->load();
        $this->assertEquals('', getenv('ASSERTVAR3'));

        $dotenv->required('ASSERTVAR3')->notEmpty();
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage One or more environment variables failed assertions: ASSERTVAR3 is empty
     */
    public function testDotenvHitsLastChain()
    {
        $dotenv = new Dotenv($this->fixturesFolder, 'assertions.env');
        $dotenv->load();
        $dotenv->required('ASSERTVAR3')->notEmpty();
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage One or more environment variables failed assertions: foo is missing
     */
    public function testDotenvValidateRequiredWithoutLoading()
    {
        $dotenv = new Dotenv($this->fixturesFolder, 'assertions.env');
        $dotenv->required('foo');
    }
}
