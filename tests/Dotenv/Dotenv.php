<?php
class DotenvTest extends \PHPUnit_Framework_TestCase
{
    public function testDotenvLoadsEnvironmentVars()
    {
        Dotenv::load(dirname(__DIR__) . '/fixtures');
        $this->assertEquals('bar', getenv('FOO'));
        $this->assertEquals('baz', getenv('BAR'));
        $this->assertEquals('with spaces', getenv('SPACED'));
        $this->assertEquals('', getenv('NULL'));
    }

    public function testQuotedDotenvLoadsEnvironmentVars()
    {
        Dotenv::load(dirname(__DIR__) . '/fixtures', 'quoted.env');
        $this->assertEquals('bar', getenv('QFOO'));
        $this->assertEquals('baz', getenv('QBAR'));
        $this->assertEquals('with spaces', getenv('QSPACED'));
        $this->assertEquals('', getenv('QNULL'));
        $this->assertEquals('pgsql:host=localhost;dbname=test', getenv('QEQUALS'));
    }

    public function testExportedDotenvLoadsEnvironmentVars()
    {
        Dotenv::load(dirname(__DIR__) . '/fixtures', 'exported.env');
        $this->assertEquals('bar', getenv('EFOO'));
        $this->assertEquals('baz', getenv('EBAR'));
        $this->assertEquals('with spaces', getenv('ESPACED'));
        $this->assertEquals('', getenv('ENULL'));
    }

    public function testDotenvLoadsEnvGlobals()
    {
        Dotenv::load(dirname(__DIR__) . '/fixtures');
        $this->assertEquals('bar', $_SERVER['FOO']);
        $this->assertEquals('baz', $_SERVER['BAR']);
        $this->assertEquals('with spaces', $_SERVER['SPACED']);
        $this->assertEquals('', $_SERVER['NULL']);
    }

    public function testDotenvLoadsServerGlobals()
    {
        Dotenv::load(dirname(__DIR__) . '/fixtures');
        $this->assertEquals('bar', $_ENV['FOO']);
        $this->assertEquals('baz', $_ENV['BAR']);
        $this->assertEquals('with spaces', $_ENV['SPACED']);
        $this->assertEquals('', $_ENV['NULL']);
    }

    public function testDotenvRequiredStringEnvironmentVars()
    {
        Dotenv::load(dirname(__DIR__) . '/fixtures');
        $res = Dotenv::required('NULL');
        $this->assertTrue($res);
    }

    public function testDotenvRequiredArrayEnvironmentVars()
    {
        Dotenv::load(dirname(__DIR__) . '/fixtures');
        $res = Dotenv::required(array('FOO', 'BAR'));
        $this->assertTrue($res);
    }

    public function testDotenvNestedEnvironmentVars()
    {
        Dotenv::load(dirname(__DIR__) . '/fixtures', 'nested.env');
        $this->assertEquals('foo', $_ENV['NFOO']);
        $this->assertEquals('bar', $_ENV['NBAR']);
        $this->assertEquals('foobar', $_ENV['NFOOBAR']);
        $this->assertEquals('foo', $_ENV['NFOOBAZ']);
    }

    public function testDotenvAllowedValues ()
    {
        Dotenv::load(dirname(__DIR__) . '/fixtures');
        $res = Dotenv::required('FOO', array('bar', 'baz'));
        $this->assertTrue($res);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Required environment variable missing or value not allowed: 'FOO'
     */
    public function testDotenvProhibitedValues ()
    {
        Dotenv::load(dirname(__DIR__) . '/fixtures');
        $res = Dotenv::required('FOO', array('buzz'));
        $this->assertTrue($res);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Required environment variable missing or value not allowed: 'FOOX', 'NOPE'
     */
    public function testDotenvRequiredThrowsRuntimeException()
    {
        Dotenv::load(dirname(__DIR__) . '/fixtures');
        $res = Dotenv::required(array('FOOX', 'NOPE'));
    }

    public function testDotenvNullFileArgumentUsesDefault()
    {
        Dotenv::load(dirname(__DIR__) . '/fixtures', null);

        $this->assertEquals('bar', getenv('FOO'));
    }

    /**
     * The fixture data has whitespace between the key and in the value string
     *     Test that these keys are trimmed down
     */
    public function testDotenvTrimmedKeys()
    {
        Dotenv::load(dirname(__DIR__) . '/fixtures', 'quoted.env');
        $this->assertTrue(isset($_ENV['QWHITESPACE']));
    }

    public function testDotenvDoesNotOverwriteEnv()
    {
        putenv('QFOO=external');
        Dotenv::load(dirname(__DIR__) . '/fixtures', 'quoted.env');
        $this->assertEquals('external', getenv('QFOO'));
    }
}
