<?php

use Dotenv\Dotenv;

class DotenvTest extends \PHPUnit_Framework_TestCase
{
    /** @var Dotenv */
    private $dotenv;

    /** @var string */
    private $fixturesFolder;

    protected function setUp()
    {
        $this->dotenv = new Dotenv();
        $this->fixturesFolder = dirname(__DIR__) . '/fixtures/env';
        $this->fixturesFolderWrong = dirname(__DIR__) . '/fixtures/env-wrong';
    }

    public function testDotenvLoadsEnvironmentVars()
    {
        $this->dotenv->load($this->fixturesFolder);
        $this->assertEquals('bar', getenv('FOO'));
        $this->assertEquals('baz', getenv('BAR'));
        $this->assertEquals('with spaces', getenv('SPACED'));
        $this->assertEquals('', getenv('NULL'));
    }

    public function testCommentedDotenvLoadsEnvironmentVars()
    {
        $this->dotenv->load($this->fixturesFolder, 'commented.env');
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
        $this->dotenv->load($this->fixturesFolder, 'quoted.env');
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
        $this->dotenv->load($this->fixturesFolderWrong, 'spaced-wrong.env');
    }

    public function testExportedDotenvLoadsEnvironmentVars()
    {
        $this->dotenv->load($this->fixturesFolder, 'exported.env');
        $this->assertEquals('bar', getenv('EFOO'));
        $this->assertEquals('baz', getenv('EBAR'));
        $this->assertEquals('with spaces', getenv('ESPACED'));
        $this->assertEquals('', getenv('ENULL'));
    }

    public function testDotenvLoadsEnvGlobals()
    {
        $this->dotenv->load($this->fixturesFolder);
        $this->assertEquals('bar', $_SERVER['FOO']);
        $this->assertEquals('baz', $_SERVER['BAR']);
        $this->assertEquals('with spaces', $_SERVER['SPACED']);
        $this->assertEquals('', $_SERVER['NULL']);
    }

    public function testDotenvLoadsServerGlobals()
    {
        $this->dotenv->load($this->fixturesFolder);
        $this->assertEquals('bar', $_ENV['FOO']);
        $this->assertEquals('baz', $_ENV['BAR']);
        $this->assertEquals('with spaces', $_ENV['SPACED']);
        $this->assertEquals('', $_ENV['NULL']);
    }

    public function testDotenvRequiredStringEnvironmentVars()
    {
        $this->dotenv->load($this->fixturesFolder);
        $this->dotenv->required('FOO');
        $this->assertTrue(true); // anything wrong an an exception will be thrown
    }

    public function testDotenvRequiredArrayEnvironmentVars()
    {
        $this->dotenv->load($this->fixturesFolder);
        $this->dotenv->required(array('FOO', 'BAR'));
        $this->assertTrue(true); // anything wrong an an exception will be thrown
    }

    public function testDotenvNestedEnvironmentVars()
    {
        $this->dotenv->load($this->fixturesFolder, 'nested.env');
        $this->assertEquals('{$NVAR1} {$NVAR2}', $_ENV['NVAR3']); // not resolved
        $this->assertEquals('Hello World!', $_ENV['NVAR4']);
        $this->assertEquals('$NVAR1 {NVAR2}', $_ENV['NVAR5']); // not resolved
    }

    public function testDotenvNestedNonExistentEnvironmentVars()
    {
        $this->dotenv->load($this->fixturesFolder, 'nested.env');
        $this->assertEquals('variable', $_ENV['NVAR6']); // resolved as empty
    }

    public function testDotenvEscapedNestedEnvironmentVars()
    {
        $this->dotenv->load($this->fixturesFolder, 'nested.env');
        $this->assertEquals('${NVAR1} ${NVAR2}', $_ENV['NVAR7']); // not resolved
    }

    public function testDotenvAllowedValues()
    {
        $this->dotenv->load($this->fixturesFolder);
        $this->dotenv->required('FOO')->allowedValues(array('bar', 'baz'));
        $this->assertTrue(true); // anything wrong an an exception will be thrown
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage One or more environment variables failed assertions: FOO is not an allowed value
     */
    public function testDotenvProhibitedValues()
    {
        $this->dotenv->load($this->fixturesFolder);
        $this->dotenv->required('FOO')->allowedValues(array('buzz'));
        $this->assertTrue(true); // anything wrong an an exception will be thrown
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage One or more environment variables failed assertions: FOOX is missing, NOPE is missing
     */
    public function testDotenvRequiredThrowsRuntimeException()
    {
        $this->dotenv->load($this->fixturesFolder);
        $this->dotenv->required(array('FOOX', 'NOPE'));
    }

    public function testDotenvNullFileArgumentUsesDefault()
    {
        $this->dotenv->load($this->fixturesFolder, null);
        $this->assertEquals('bar', getenv('FOO'));
    }

    /**
     * The fixture data has whitespace between the key and in the value string
     *     Test that these keys are trimmed down
     */
    public function testDotenvTrimmedKeys()
    {
        $this->dotenv->load($this->fixturesFolder, 'quoted.env');
        $this->assertTrue(isset($_ENV['QWHITESPACE']));
    }

    public function testDotenvLoadDoesNotOverwriteEnv()
    {
        $this->dotenv->put('IMMUTABLE', 'true');
        $this->dotenv->load($this->fixturesFolder, 'immutable.env');
        $this->assertEquals('true', getenv('IMMUTABLE'));
    }

    public function testDotenvOverloadDoesOverwriteEnv()
    {
        $this->dotenv->put('MUTABLE', 'false');
        $this->dotenv->overload($this->fixturesFolder, 'mutable.env');
        $this->assertEquals('true', getenv('MUTABLE'));
    }

    public function testDotenvAllowsSpecialCharacters()
    {
        $this->dotenv->load($this->fixturesFolder, 'specialchars.env');
        $this->assertEquals('$a6^C7k%zs+e^.jvjXk', getenv('SPVAR1'));
        $this->assertEquals('?BUty3koaV3%GA*hMAwH}B', getenv('SPVAR2'));
        $this->assertEquals('jdgEB4{QgEC]HL))&GcXxokB+wqoN+j>xkV7K?m$r', getenv('SPVAR3'));
        $this->assertEquals('22222:22#2^{', getenv('SPVAR4'));
        $this->assertEquals("test some escaped characters like a quote \" or maybe a backslash \\", getenv('SPVAR5'));
    }

    public function testDotenvAssertions()
    {
        $this->dotenv->load($this->fixturesFolder, 'assertions.env');
        $this->dotenv->required(array(
            'ASSERTVAR1',
            'ASSERTVAR2',
            'ASSERTVAR3',
            'ASSERTVAR4',
        ));

        $this->dotenv->required(array(
            'ASSERTVAR1',
            'ASSERTVAR4',
        ))->notEmpty();

        $this->dotenv->required(array(
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
        $this->dotenv->load($this->fixturesFolder, 'assertions.env');
        $this->dotenv->required('ASSERTVAR2')->notEmpty();
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage One or more environment variables failed assertions: ASSERTVAR3 is empty
     */
    public function testDotenvStringOfSpacesConsideredEmpty()
    {
        $this->dotenv->load($this->fixturesFolder, 'assertions.env');
        $this->dotenv->required('ASSERTVAR3')->notEmpty();
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage One or more environment variables failed assertions: ASSERTVAR3 is empty
     */
    public function testDotenvHitsLastChain()
    {
        $this->dotenv->load($this->fixturesFolder, 'assertions.env');
        $this->dotenv->required('ASSERTVAR3')->notEmpty();
    }
}
