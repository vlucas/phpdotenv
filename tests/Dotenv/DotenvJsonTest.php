<?php

use Dotenv\Dotenv;

class DotenvJsonTest extends \PHPUnit_Framework_TestCase
{
    /** @var Dotenv */
    private $dotenv;

    /** @var string */
    private $fixturesFolder;

    protected function setUp()
    {
        $this->dotenv = new Dotenv();
        $this->fixturesFolder = dirname(__DIR__) . '/fixtures/json';
    }

    public function testDotenvLoadsEnvironmentVars()
    {
        $this->dotenv->load($this->fixturesFolder);
        $this->assertEquals('bar', getenv('PE_FOO'));
        $this->assertEquals('baz', getenv('PE_BAR'));
    }

    public function testDotenvRequiredStringEnvironmentVars()
    {
        $this->dotenv->load($this->fixturesFolder, '.env.json');
        $this->dotenv->required('PE_FOO');
        $this->assertTrue(true); // anything wrong an an exception will be thrown
    }

    public function testDotenvRequiredArrayEnvironmentVars()
    {
        $this->dotenv->load($this->fixturesFolder, '.env.json');
        $this->dotenv->required(array('PE_FOO', 'PE_BAR'));
        $this->assertTrue(true); // anything wrong an an exception will be thrown
    }

    public function testDotenvNestedEnvironmentVars()
    {
        $this->dotenv->load($this->fixturesFolder, 'nested.env.json');
        $this->assertEquals('{$PNVAR1} {$PNVAR2}', $_ENV['PNVAR3']); // not resolved
        $this->assertEquals('Hello World!', $_ENV['PNVAR4']);
        $this->assertEquals('$PNVAR1 {PNVAR2}', $_ENV['PNVAR5']); // not resolved
    }

    public function testDotenvIgnoresEmptyFile()
    {
        $this->dotenv->load($this->fixturesFolder, 'empty.env.json');
        $this->assertTrue(true);
    }

    public function testDotenvIgnoresNonJson()
    {
        $this->dotenv->load($this->fixturesFolder, 'nonjson.env.json');
        $this->assertTrue(true);
    }

    public function testDotenvCastsAllValuesToString()
    {
        $this->dotenv->load($this->fixturesFolder, 'stringvalues.env.json');
        $assertions = array(
            'PS_INT'    => '1',
            'PS_FLOAT'  => '20.5',
            'PS_STRING' => 'string',
            'PS_FALSE'  => 'false',
            'PS_TRUE'  =>  'true',
            'PS_ARRAY'  => '',
            'PS_HASH'   => "",
            'PS_OBJECT' => '',
        );
        foreach ($assertions as $k => $v) {
            $this->assertEquals($v, $_ENV[$k]);
        }
    }
}
