<?php

use Dotenv\Dotenv;

class DotenvPhpTest extends \PHPUnit_Framework_TestCase
{
    /** @var Dotenv */
    private $dotenv;

    protected function setUp()
    {
        $this->dotenv = new Dotenv();
    }

    public function testDotenvLoadsEnvironmentVars()
    {
        $this->dotenv->load(dirname(__DIR__) . '/fixtures/php');
        $this->assertEquals('bar', getenv('PE_FOO'));
        $this->assertEquals('baz', getenv('PE_BAR'));
    }

    public function testDotenvRequiredStringEnvironmentVars()
    {
        $this->dotenv->load(dirname(__DIR__) . '/fixtures/php', '.env.php');
        $this->dotenv->exists('PE_FOO');
        $this->assertTrue(true); // anything wrong an an exception will be thrown
    }

    public function testDotenvRequiredArrayEnvironmentVars()
    {
        $this->dotenv->load(dirname(__DIR__) . '/fixtures/php', '.env.php');
        $this->dotenv->exists(array('PE_FOO', 'PE_BAR'));
        $this->assertTrue(true); // anything wrong an an exception will be thrown
    }

    public function testDotenvNestedEnvironmentVars()
    {
        $this->dotenv->load(dirname(__DIR__) . '/fixtures/php', 'nested.env.php');
        $this->assertEquals('Hello World!', $_ENV['PNVAR3']);
        $this->assertEquals('${PNVAR1} ${PNVAR2}', $_ENV['PNVAR4']); // not resolved
        $this->assertEquals('$PNVAR1 {PNVAR2}', $_ENV['PNVAR5']); // not resolved
    }

    public function testDotenvIgnoresEmptyFile()
    {
        $this->dotenv->load(dirname(__DIR__) . '/fixtures/php', 'empty.env.php');
        $this->assertTrue(true);
    }

    public function testDotenvIgnoresNonArray()
    {
        $this->dotenv->load(dirname(__DIR__) . '/fixtures/php', 'nonarray.env.php');
        $this->assertTrue(true);
    }

    public function testDotenvCastsAllValuesToString()
    {
        $this->dotenv->load(dirname(__DIR__) . '/fixtures/php', 'stringvalues.env.php');
        $assertions = array(
            'PS_INT'    => '1',
            'PS_FLOAT'  => '20.5',
            'PS_STRING' => 'string',
            'PS_FALSE'  => 'false',
            'PS_TRUE'  =>  'true',
            'PS_ARRAY'  => '',
            'PS_HASH'   => "",
            'PS_OBJECT' => ''
        );
        foreach ($assertions as $k => $v) {
            $this->assertEquals($v, $_ENV[$k]);
        }
    }
}
