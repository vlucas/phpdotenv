<?php

class HelpersTest extends PHPUnit_Framework_Testcase
{
    /**
     * Holds an environment.
     *
     * @var \Dotenv\Dotenv
     */
    private $env;

    /**
     * Setup a new enviroment.
     *
     * @return void
     */
    public function setup()
    {
        $this->env = create_env(__DIR__ . '/../fixtures/env');
    }

    public function testDotEnv()
    {
        $this->env->load();

        $this->assertSame('bar', getenv('FOO'));
        $this->assertSame('baz', getenv('BAR'));
    }
}