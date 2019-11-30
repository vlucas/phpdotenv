
<?php

use Dotenv\Dotenv;
use Dotenv\Loader\Loader;
use Dotenv\Repository\Adapter\ArrayAdapter;
use Dotenv\Repository\RepositoryBuilder;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    /**
     * @var string
     */
    private $folder;

    /**
     * @var string[]|null
     */
    protected $keyVal;

    public function setUp()
    {
        $this->folder = dirname(__DIR__).'/fixtures/env';
        $this->keyVal(true);
    }

    protected function load()
    {
        $dotenv = Dotenv::createImmutable($this->folder);
        $dotenv->load();
    }

    /**
     * Generates a new key/value pair or returns the previous one.
     *
     * Since most of our functionality revolves around setting/retrieving keys
     * and values, we have this utility function to help generate new, unique
     * key/value pairs.
     *
     * @param bool $reset
     *
     * @return array
     */
    protected function keyVal($reset = false)
    {
        if (!isset($this->keyVal) || $reset) {
            $this->keyVal = [uniqid() => uniqid()];
        }

        return $this->keyVal;
    }

    /**
     * Returns the key from keyVal(), without reset.
     *
     * @return string
     */
    protected function key()
    {
        $keyVal = $this->keyVal();

        return key($keyVal);
    }

    /**
     * Returns the value from keyVal(), without reset.
     *
     * @return string
     */
    protected function value()
    {
        $keyVal = $this->keyVal();

        return reset($keyVal);
    }

    public function testMutableLoaderClearsEnvironmentVars()
    {
        $repository = RepositoryBuilder::create()->make();

        // Set an environment variable.
        $repository->set($this->key(), $this->value());

        // Clear the set environment variable.
        $repository->clear($this->key());
        $this->assertSame(null, $repository->get($this->key()));
        $this->assertSame(false, getenv($this->key()));
        $this->assertSame(false, isset($_ENV[$this->key()]));
        $this->assertSame(false, isset($_SERVER[$this->key()]));
    }

    public function testImmutableLoaderCannotClearEnvironmentVars()
    {
        $this->load();

        $repository = RepositoryBuilder::create()->immutable()->make();

        // Set an environment variable.
        $repository->set($this->key(), $this->value());

        // Attempt to clear the environment variable, check that it fails.
        $repository->clear($this->key());
        $this->assertSame($this->value(), $repository->get($this->key()));
        $this->assertSame($this->value(), getenv($this->key()));
        $this->assertSame(true, isset($_ENV[$this->key()]));
        $this->assertSame(true, isset($_SERVER[$this->key()]));
    }

    public function testCheckingWhetherVariableExists()
    {
        $this->load();

        $repo = RepositoryBuilder::create()->make();

        $this->assertTrue($repo->has('FOO'));
        $this->assertFalse($repo->has('NON_EXISTING_VARIABLE'));
    }

    public function testCheckingHasWithBadType()
    {
        $this->load();

        $repo = RepositoryBuilder::create()->make();

        $this->assertFalse($repo->has(123));
        $this->assertFalse($repo->has(null));
    }

    public function testGettingVariableByName()
    {
        $this->load();

        $repo = RepositoryBuilder::create()->make();

        $this->assertSame('bar', $repo->get('FOO'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected name to be a string.
     */
    public function testGettingBadVariable()
    {
        $repo = RepositoryBuilder::create()->make();

        $repo->get(null);
    }

    public function testSettingVariable()
    {
        $this->load();

        $repo = RepositoryBuilder::create()->make();

        $this->assertSame('bar', $repo->get('FOO'));
        $repo->set('FOO', 'new');
        $this->assertSame('new', $repo->get('FOO'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected name to be a string.
     */
    public function testSettingBadVariable()
    {
        $repo = RepositoryBuilder::create()->make();

        $repo->set(null, 'foo');
    }

    public function testClearingVariable()
    {
        $this->load();

        $repo = RepositoryBuilder::create()->make();

        $this->assertTrue($repo->has('FOO'));
        $repo->clear('FOO');
        $this->assertFalse($repo->has('FOO'));
    }

    public function testClearingVariableWithArrayAdapter()
    {
        $adapters = [new ArrayAdapter()];
        $repo = RepositoryBuilder::create()->withReaders($adapters)->withWriters($adapters)->make();

        $this->assertFalse($repo->has('FOO'));
        $repo->set('FOO', 'BAR');
        $this->assertTrue($repo->has('FOO'));
        $repo->clear('FOO');
        $this->assertFalse($repo->has('FOO'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected name to be a string.
     */
    public function testClearingBadVariable()
    {
        $repo = RepositoryBuilder::create()->make();

        $repo->clear(null);
    }

    public function testCannotSetVariableOnImmutableInstance()
    {
        $this->load();

        $repo = RepositoryBuilder::create()->immutable()->make();

        $this->assertSame('bar', $repo->get('FOO'));

        $repo->set('FOO', 'new');

        $this->assertSame('bar', $repo->get('FOO'));
    }

    public function testCannotClearVariableOnImmutableInstance()
    {
        $this->load();

        $repo = RepositoryBuilder::create()->immutable()->make();

        $repo->clear('FOO');

        $this->assertTrue($repo->has('FOO'));
    }

    public function testCheckingWhetherVariableExistsUsingArrayNotation()
    {
        $this->load();

        $repo = RepositoryBuilder::create()->make();

        $this->assertTrue(isset($repo['FOO']));
        $this->assertFalse(isset($repo['NON_EXISTING_VARIABLE']));
    }

    public function testGettingVariableByNameUsingArrayNotation()
    {
        $this->load();

        $repo = RepositoryBuilder::create()->make();

        $this->assertSame('bar', $repo['FOO']);
    }

    public function testSettingVariableUsingArrayNotation()
    {
        $this->load();

        $repo = RepositoryBuilder::create()->make();

        $this->assertSame('bar', $repo['FOO']);

        $repo['FOO'] = 'new';

        $this->assertSame('new', $repo['FOO']);
    }

    public function testClearingVariableUsingArrayNotation()
    {
        $this->load();

        $repo = RepositoryBuilder::create()->make();

        unset($repo['FOO']);

        $this->assertFalse(isset($repo['FOO']));
    }
}
