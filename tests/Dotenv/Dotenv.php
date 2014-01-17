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

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Required ENV vars missing: 'FOOX', 'NOPE'
     */
    public function testDotenvRequiredThrowsRuntimeException()
    {
        Dotenv::load(dirname(__DIR__) . '/fixtures');
        $res = Dotenv::required(array('FOOX', 'NOPE'));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Environment file .env not found.
     */
    public function testDotenvNotFoundThrowsInvalidArgumentException()
    {
        Dotenv::load(dirname(__DIR__) . '/badpath');
    }

    public function testDotenvNotFoundFailsGracefullyWithOptionFlag()
    {
        Dotenv::load(dirname(__DIR__) . '/badpath', '.env', true);

        $this->assertFalse(is_file(dirname(__DIR__) . '/badpath/.env'));
    }
}

