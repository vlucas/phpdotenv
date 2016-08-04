<?php

class HelpersTest extends PHPUnit_Framework_TestCase
{
    /**
     * Setup a new enviroment.
     *
     * @return void
     */
    public function setup()
    {
        create_env(__DIR__ . '/../fixtures/env');
    }

    public function testDotEnv()
    {
        $this->assertSame('bar', getenv('FOO'));
        $this->assertSame('baz', getenv('BAR'));
    }
}