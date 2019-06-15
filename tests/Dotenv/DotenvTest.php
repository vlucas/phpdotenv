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
     * @expectedExceptionMessage Unable to read any of the environment file(s) at
     */
    public function testDotenvThrowsExceptionIfUnableToLoadFile()
    {
        $dotenv = Dotenv::create(__DIR__);
        $dotenv->load();
    }

    /**
     * @expectedException \Dotenv\Exception\InvalidPathException
     * @expectedExceptionMessage Unable to read any of the environment file(s) at
     */
    public function testDotenvThrowsExceptionIfUnableToLoadFiles()
    {
        $dotenv = Dotenv::create([__DIR__, __DIR__.'/foo/bar']);
        $dotenv->load();
    }

    public function testDotenvTriesPathsToLoad()
    {
        $dotenv = Dotenv::create([__DIR__, $this->fixturesFolder]);
        $this->assertCount(4, $dotenv->load());
    }

    public function testDotenvSkipsLoadingIfFileIsMissing()
    {
        $dotenv = Dotenv::create(__DIR__);
        $this->assertSame([], $dotenv->safeLoad());
    }

    public function testDotenvLoadsEnvironmentVars()
    {
        $dotenv = Dotenv::create($this->fixturesFolder);
        $this->assertSame(
            ['FOO' => 'bar', 'BAR' => 'baz', 'SPACED' => 'with spaces', 'NULL' => ''],
            $dotenv->load()
        );
        $this->assertSame('bar', getenv('FOO'));
        $this->assertSame('baz', getenv('BAR'));
        $this->assertSame('with spaces', getenv('SPACED'));
        $this->assertEmpty(getenv('NULL'));
    }

    public function testCommentedDotenvLoadsEnvironmentVars()
    {
        $dotenv = Dotenv::create($this->fixturesFolder, 'commented.env');
        $dotenv->load();
        $this->assertSame('bar', getenv('CFOO'));
        $this->assertFalse(getenv('CBAR'));
        $this->assertFalse(getenv('CZOO'));
        $this->assertSame('with spaces', getenv('CSPACED'));
        $this->assertSame('a value with a # character', getenv('CQUOTES'));
        $this->assertSame('a value with a # character & a quote " character inside quotes', getenv('CQUOTESWITHQUOTE'));
        $this->assertEmpty(getenv('CNULL'));
        $this->assertEmpty(getenv('EMPTY'));
        $this->assertEmpty(getenv('EMPTY2'));
        $this->assertSame('foo', getenv('FOOO'));
    }

    public function testQuotedDotenvLoadsEnvironmentVars()
    {
        $dotenv = Dotenv::create($this->fixturesFolder, 'quoted.env');
        $dotenv->load();
        $this->assertSame('bar', getenv('QFOO'));
        $this->assertSame('baz', getenv('QBAR'));
        $this->assertSame('with spaces', getenv('QSPACED'));
        $this->assertEmpty(getenv('QNULL'));

        $this->assertSame('pgsql:host=localhost;dbname=test', getenv('QEQUALS'));
        $this->assertSame('test some escaped characters like a quote (") or maybe a backslash (\\)', getenv('QESCAPED'));
        $this->assertSame('iiiiviiiixiiiiviiii\\n', getenv('QSLASH'));

        $this->assertSame('test some escaped characters like a quote (\') or maybe a backslash (\\)', getenv('SQESCAPED'));
        $this->assertSame('iiiiviiiixiiiiviiii\\n', getenv('SQSLASH'));
    }

    public function testLargeDotenvLoadsEnvironmentVars()
    {
        $dotenv = Dotenv::create($this->fixturesFolder, 'large.env');
        $dotenv->load();
        $this->assertNotEmpty(getenv('LARGE'));
    }

    public function testMultipleDotenvLoadsEnvironmentVars()
    {
        $dotenv = Dotenv::create($this->fixturesFolder, 'multiple.env');
        $dotenv->load();
        $this->assertSame('bar', getenv('MULTI1'));
        $this->assertSame('foo', getenv('MULTI2'));
    }

    public function testExportedDotenvLoadsEnvironmentVars()
    {
        $dotenv = Dotenv::create($this->fixturesFolder, 'exported.env');
        $dotenv->load();
        $this->assertSame('bar', getenv('EFOO'));
        $this->assertSame('baz', getenv('EBAR'));
        $this->assertSame('with spaces', getenv('ESPACED'));
        $this->assertEmpty(getenv('ENULL'));
    }

    public function testDotenvLoadsEnvGlobals()
    {
        $dotenv = Dotenv::create($this->fixturesFolder);
        $dotenv->load();
        $this->assertSame('bar', $_SERVER['FOO']);
        $this->assertSame('baz', $_SERVER['BAR']);
        $this->assertSame('with spaces', $_SERVER['SPACED']);
        $this->assertEmpty($_SERVER['NULL']);
    }

    public function testDotenvLoadsServerGlobals()
    {
        $dotenv = Dotenv::create($this->fixturesFolder);
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
        $dotenv = Dotenv::create($this->fixturesFolder);
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
        $dotenv = Dotenv::create($this->fixturesFolder);
        $dotenv->load();
        $dotenv->required(['FOO', 'BAR']);
        $this->assertTrue(true); // anything wrong an exception will be thrown
    }

    public function testDotenvNestedEnvironmentVars()
    {
        $dotenv = Dotenv::create($this->fixturesFolder, 'nested.env');
        $dotenv->load();
        $this->assertSame('{$NVAR1} {$NVAR2}', $_ENV['NVAR3']); // not resolved
        $this->assertSame('Hello World!', $_ENV['NVAR4']);
        $this->assertSame('$NVAR1 {NVAR2}', $_ENV['NVAR5']); // not resolved
        $this->assertSame('Special Value', $_ENV['N.VAR6']); // new '.' (dot) in var name
        $this->assertSame('Special Value', $_ENV['NVAR7']);  // nested '.' (dot) variable
        $this->assertSame('', $_ENV['NVAR8']);
        $this->assertSame('', $_ENV['NVAR9']);  // nested variable is empty string
        $this->assertSame('${NVAR888}', $_ENV['NVAR10']);  // nested variable is not set
    }

    /**
     * @depends testDotenvLoadsEnvironmentVars
     * @depends testDotenvLoadsEnvGlobals
     * @depends testDotenvLoadsServerGlobals
     */
    public function testDotenvAllowedValues()
    {
        $dotenv = Dotenv::create($this->fixturesFolder);
        $dotenv->load();
        $dotenv->required('FOO')->allowedValues(['bar', 'baz']);
        $this->assertTrue(true); // anything wrong an exception will be thrown
    }

    /**
     * @depends testDotenvLoadsEnvironmentVars
     * @depends testDotenvLoadsEnvGlobals
     * @depends testDotenvLoadsServerGlobals
     *
     * @expectedException \Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: FOO is not one of [buzz, buz].
     */
    public function testDotenvProhibitedValues()
    {
        $dotenv = Dotenv::create($this->fixturesFolder);
        $dotenv->load();
        $dotenv->required('FOO')->allowedValues(['buzz', 'buz']);
    }

    /**
     * @expectedException \Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: FOOX is missing, NOPE is missing.
     */
    public function testDotenvRequiredThrowsRuntimeException()
    {
        $dotenv = Dotenv::create($this->fixturesFolder);
        $dotenv->load();
        $this->assertFalse(getenv('FOOX'));
        $this->assertFalse(getenv('NOPE'));
        $dotenv->required(['FOOX', 'NOPE']);
    }

    public function testDotenvNullFileArgumentUsesDefault()
    {
        $dotenv = Dotenv::create($this->fixturesFolder, null);
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
        $dotenv = Dotenv::create($this->fixturesFolder, 'quoted.env');
        $dotenv->load();
        $this->assertSame('no space', getenv('QWHITESPACE'));
    }

    public function testDotenvLoadDoesNotOverwriteEnv()
    {
        putenv('IMMUTABLE=true');
        $dotenv = Dotenv::create($this->fixturesFolder, 'immutable.env');
        $dotenv->load();
        $this->assertSame('true', getenv('IMMUTABLE'));
    }

    public function testDotenvLoadAfterOverload()
    {
        putenv('IMMUTABLE=true');
        $dotenv = Dotenv::create($this->fixturesFolder, 'immutable.env');
        $dotenv->overload();
        $this->assertSame('false', getenv('IMMUTABLE'));

        putenv('IMMUTABLE=true');
        $dotenv->load();
        $this->assertSame('true', getenv('IMMUTABLE'));
    }

    public function testDotenvOverloadAfterLoad()
    {
        putenv('IMMUTABLE=true');
        $dotenv = Dotenv::create($this->fixturesFolder, 'immutable.env');
        $dotenv->load();
        $this->assertSame('true', getenv('IMMUTABLE'));

        putenv('IMMUTABLE=true');
        $dotenv->overload();
        $this->assertSame('false', getenv('IMMUTABLE'));
    }

    public function testDotenvOverloadDoesOverwriteEnv()
    {
        $dotenv = Dotenv::create($this->fixturesFolder, 'mutable.env');
        $dotenv->overload();
        $this->assertSame('true', getenv('MUTABLE'));
    }

    public function testDotenvAllowsSpecialCharacters()
    {
        $dotenv = Dotenv::create($this->fixturesFolder, 'specialchars.env');
        $dotenv->load();
        $this->assertSame('$a6^C7k%zs+e^.jvjXk', getenv('SPVAR1'));
        $this->assertSame('?BUty3koaV3%GA*hMAwH}B', getenv('SPVAR2'));
        $this->assertSame('jdgEB4{QgEC]HL))&GcXxokB+wqoN+j>xkV7K?m$r', getenv('SPVAR3'));
        $this->assertSame('22222:22#2^{', getenv('SPVAR4'));
        $this->assertSame('test some escaped characters like a quote " or maybe a backslash \\', getenv('SPVAR5'));
        $this->assertSame('secret!@', getenv('SPVAR6'));
        $this->assertSame('secret!@#', getenv('SPVAR7'));
        $this->assertSame('secret!@#', getenv('SPVAR8'));
    }

    public function testMutlilineLoading()
    {
        $dotenv = Dotenv::create($this->fixturesFolder, 'multiline.env');
        $dotenv->load();
        $this->assertSame("test\n     test\"test\"\n     test", getenv('TEST'));
        $this->assertSame('https://vision.googleapis.com/v1/images:annotate?key=', getenv('TEST_EQD'));
        $this->assertSame('https://vision.googleapis.com/v1/images:annotate?key=', getenv('TEST_EQS'));
    }

    public function testDotenvAssertions()
    {
        $dotenv = Dotenv::create($this->fixturesFolder, 'assertions.env');
        $dotenv->load();
        $this->assertSame('val1', getenv('ASSERTVAR1'));
        $this->assertEmpty(getenv('ASSERTVAR2'));
        $this->assertSame('val3   ', getenv('ASSERTVAR3'));
        $this->assertSame('0', getenv('ASSERTVAR4'));
        $this->assertSame('#foo', getenv('ASSERTVAR5'));
        $this->assertSame("val1\nval2", getenv('ASSERTVAR6'));
        $this->assertSame("\nval3", getenv('ASSERTVAR7'));
        $this->assertSame("val3\n", getenv('ASSERTVAR8'));

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

        $this->assertTrue(true); // anything wrong an an exception will be thrown
    }

    /**
     * @expectedException \Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: ASSERTVAR2 is empty.
     */
    public function testDotenvEmptyThrowsRuntimeException()
    {
        $dotenv = Dotenv::create($this->fixturesFolder, 'assertions.env');
        $dotenv->load();
        $this->assertEmpty(getenv('ASSERTVAR2'));

        $dotenv->required('ASSERTVAR2')->notEmpty();
    }

    /**
     * @expectedException \Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: ASSERTVAR9 is empty.
     */
    public function testDotenvStringOfSpacesConsideredEmpty()
    {
        $dotenv = Dotenv::create($this->fixturesFolder, 'assertions.env');
        $dotenv->load();
        $dotenv->required('ASSERTVAR9')->notEmpty();
    }

    /**
     * @expectedException \Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: foo is missing.
     */
    public function testDotenvValidateRequiredWithoutLoading()
    {
        $dotenv = Dotenv::create($this->fixturesFolder, 'assertions.env');
        $dotenv->required('foo');
    }

    public function testDotenvRequiredCanBeUsedWithoutLoadingFile()
    {
        putenv('REQUIRED_VAR=1');
        $dotenv = Dotenv::create($this->fixturesFolder);
        $dotenv->required('REQUIRED_VAR')->notEmpty();
        $this->assertTrue(true);
    }

    public function testGetEnvironmentVariablesList()
    {
        $dotenv = Dotenv::create($this->fixturesFolder);
        $dotenv->load();
        $this->assertSame(['FOO', 'BAR', 'SPACED', 'NULL'], $dotenv->getEnvironmentVariableNames());
    }
}
