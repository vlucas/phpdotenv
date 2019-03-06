<?php

use Dotenv\Environment\DotenvFactory;
use Dotenv\Loader;
use PHPUnit\Framework\TestCase;

class EnvironmentVariablesTest extends TestCase
{
    /**
     * @var \Dotenv\Environment\DotenvFactory
     */
    private $envFactory;

    protected function setUp()
    {
        $this->envFactory = new DotenvFactory();
        (new Loader([dirname(__DIR__).'/fixtures/env/.env'], $this->envFactory))->load();
    }

    public function testCheckingWhetherVariableExists()
    {
        $envVars = $this->envFactory->create();

        $this->assertTrue($envVars->has('FOO'));
        $this->assertFalse($envVars->has('NON_EXISTING_VARIABLE'));
    }

    public function testCheckingHasWithBadType()
    {
        $envVars = $this->envFactory->create();

        $this->assertFalse($envVars->has(123));
        $this->assertFalse($envVars->has(null));
    }

    public function testGettingVariableByName()
    {
        $envVars = $this->envFactory->create();

        $this->assertSame('bar', $envVars->get('FOO'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected name to be a string.
     */
    public function testGettingBadVariable()
    {
        $envVars = $this->envFactory->create();

        $envVars->get(null);
    }

    public function testSettingVariable()
    {
        $envVars = $this->envFactory->create();

        $this->assertSame('bar', $envVars->get('FOO'));

        $envVars->set('FOO', 'new');

        $this->assertSame('new', $envVars->get('FOO'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected name to be a string.
     */
    public function testSettingBadVariable()
    {
        $envVars = $this->envFactory->create();

        $envVars->set(null, 'foo');
    }

    public function testClearingVariable()
    {
        $envVars = $this->envFactory->create();

        $envVars->clear('FOO');

        $this->assertFalse($envVars->has('FOO'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected name to be a string.
     */
    public function testClearingBadVariable()
    {
        $envVars = $this->envFactory->create();

        $envVars->clear(null);
    }

    public function testCannotSetVariableOnImmutableInstance()
    {
        $envVars = $this->envFactory->createImmutable();

        $this->assertSame('bar', $envVars->get('FOO'));

        $envVars->set('FOO', 'new');

        $this->assertSame('bar', $envVars->get('FOO'));
    }

    public function testCannotClearVariableOnImmutableInstance()
    {
        $envVars = $this->envFactory->createImmutable();

        $envVars->clear('FOO');

        $this->assertTrue($envVars->has('FOO'));
    }

    public function testCheckingWhetherVariableExistsUsingArrayNotation()
    {
        $envVars = $this->envFactory->create();

        $this->assertTrue(isset($envVars['FOO']));
        $this->assertFalse(isset($envVars['NON_EXISTING_VARIABLE']));
    }

    public function testGettingVariableByNameUsingArrayNotation()
    {
        $envVars = $this->envFactory->create();

        $this->assertSame('bar', $envVars['FOO']);
    }

    public function testSettingVariableUsingArrayNotation()
    {
        $envVars = $this->envFactory->create();

        $this->assertSame('bar', $envVars['FOO']);

        $envVars['FOO'] = 'new';

        $this->assertSame('new', $envVars['FOO']);
    }

    public function testClearingVariableUsingArrayNotation()
    {
        $envVars = $this->envFactory->create();

        unset($envVars['FOO']);

        $this->assertFalse(isset($envVars['FOO']));
    }
}
