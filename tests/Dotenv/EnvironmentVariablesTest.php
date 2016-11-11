<?php

use Dotenv\EnvironmentVariables;
use Dotenv\Loader;

class EnvironmentVariablesTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $loader = new Loader(dirname(__DIR__) . '/fixtures/env/.env');
        $loader->load();
    }

    public function testCheckingWhetherVariableExists()
    {
        $envVars = EnvironmentVariables::create();

        $this->assertTrue($envVars->has('FOO'));
        $this->assertFalse($envVars->has('NON_EXISTING_VARIABLE'));
    }

    public function testGettingVariableByName()
    {
        $envVars = EnvironmentVariables::create();

        $this->assertSame('bar', $envVars->get('FOO'));
    }

    public function testSettingVariable()
    {
        $envVars = EnvironmentVariables::create();

        $this->assertSame('bar', $envVars->get('FOO'));

        $envVars->set('FOO', 'new');

        $this->assertSame('new', $envVars->get('FOO'));
    }

    public function testClearingVariable()
    {
        $envVars = EnvironmentVariables::create();

        $envVars->clear('FOO');

        $this->assertFalse($envVars->has('FOO'));
    }

    public function testCannotSetVariableOnImmutableInstance()
    {
        $envVars = EnvironmentVariables::createImmutable();

        $this->assertSame('bar', $envVars->get('FOO'));

        $envVars->set('FOO', 'new');

        $this->assertSame('bar', $envVars->get('FOO'));
    }

    public function testCannotClearVariableOnImmutableInstance()
    {
        $envVars = EnvironmentVariables::createImmutable();

        $envVars->clear('FOO');

        $this->assertTrue($envVars->has('FOO'));
    }

    public function testCheckingWhetherVariableExistsUsingArrayNotation()
    {
        $envVars = EnvironmentVariables::create();

        $this->assertTrue(isset($envVars['FOO']));
        $this->assertFalse(isset($envVars['NON_EXISTING_VARIABLE']));
    }

    public function testGettingVariableByNameUsingArrayNotation()
    {
        $envVars = EnvironmentVariables::create();

        $this->assertSame('bar', $envVars['FOO']);
    }

    public function testSettingVariableUsingArrayNotation()
    {
        $envVars = EnvironmentVariables::create();

        $this->assertSame('bar', $envVars['FOO']);

        $envVars['FOO'] = 'new';

        $this->assertSame('new', $envVars['FOO']);
    }

    public function testClearingVariableUsingArrayNotation()
    {
        $envVars = EnvironmentVariables::create();

        unset($envVars['FOO']);

        $this->assertFalse(isset($envVars['FOO']));
    }
}
