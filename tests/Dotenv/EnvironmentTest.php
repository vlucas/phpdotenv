<?php

use Dotenv\Loader;

class EnvironmentTest extends \PHPUnit_Framework_TestCase
{
    public function testImmutableEnvironmentByDefault()
    {
        $environment = new \Dotenv\Environment();

        // Set an environment variable.
        $name = 'name';
        $value = 'value';
        $environment->setVariable(\Dotenv\Variable::make($name, $value));

        // Attempt to clear the environment variable, check that it fails.
        $environment->clearVariable(\Dotenv\Variable::make($name));
        $this->assertSame($value, $environment->getVariable($name)->getValue());
        $this->assertSame($value, getenv($name));
        $this->assertSame(true, isset($_ENV[$name]));
        $this->assertSame(true, isset($_SERVER[$name]));
    }

    public function testMutableLoaderClearsEnvironmentVars()
    {
        $name = 'name';
        $value = 'value';
        
        $environment = new \Dotenv\Environment();
        $environment->setImmutable(false);

        // Set an environment variable.
            $environment->setVariable(new \Dotenv\Variable($name . '=' . $value));

        // Clear the set environment variable.
        $environment->clearVariable(\Dotenv\Variable::make($name));
        $this->assertSame(null, $environment->getVariable($name)->getValue());
        $this->assertSame(false, getenv($name));
        $this->assertSame(false, isset($_ENV[$name]));
        $this->assertSame(false, isset($_SERVER[$name]));
    }
}