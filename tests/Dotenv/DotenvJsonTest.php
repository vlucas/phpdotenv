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
        $this->assertEquals('bar', getenv('JE_FOO'));
        $this->assertEquals('baz', getenv('JE_BAR'));
    }

    public function testDotenvRequiredStringEnvironmentVars()
    {
        $this->dotenv->load($this->fixturesFolder, '.env.json');
        $this->dotenv->required('JE_FOO');
        $this->assertTrue(true); // anything wrong an an exception will be thrown
    }

    public function testDotenvRequiredArrayEnvironmentVars()
    {
        $this->dotenv->load($this->fixturesFolder, '.env.json');
        $this->dotenv->required(array('JE_FOO', 'JE_BAR'));
        $this->assertTrue(true); // anything wrong an an exception will be thrown
    }

    public function testDotenvNestedEnvironmentVars()
    {
        $this->dotenv->load($this->fixturesFolder, 'nested.env.json');
        $this->assertEquals('{$JNVAR1} {$JNVAR2}', $_ENV['JNVAR3']); // not resolved
        $this->assertEquals('Hello World!', $_ENV['JNVAR4']);
        $this->assertEquals('$JNVAR1 {JNVAR2}', $_ENV['JNVAR5']); // not resolved
    }

    public function testDotenvNestedNonExistentEnvironmentVars()
    {
        $this->dotenv->load($this->fixturesFolder, 'nested.env.json');
        $this->assertEquals('variable', $_ENV['JNVAR6']); // resolved as empty
    }

    public function testDotenvEscapedNestedEnvironmentVars()
    {
        $this->dotenv->load($this->fixturesFolder, 'nested.env.json');
        $this->assertEquals('${JNVAR1} ${JNVAR2}', $_ENV['JNVAR7']); // not resolved
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
            'JS_INT'    => '1',
            'JS_FLOAT'  => '20.5',
            'JS_STRING' => 'string',
            'JS_FALSE'  => 'false',
            'JS_TRUE'  =>  'true',
            'JS_ARRAY'  => '',
            'JS_HASH'   => "",
            'JS_OBJECT' => '',
        );
        foreach ($assertions as $k => $v) {
            $this->assertEquals($v, $_ENV[$k]);
        }
    }
}
