<?php

use Dotenv\Environment\Adapter\ArrayAdapter;
use Dotenv\Environment\DotenvFactory;
use Dotenv\Loader;
use PHPUnit\Framework\TestCase;

class LoaderTest extends TestCase
{
    /**
     * @var string
     */
    protected $folder;

    /**
     * @var string[]|null
     */
    protected $keyVal;

    public function setUp()
    {
        $this->folder = dirname(__DIR__).'/fixtures/env';
        $this->keyVal(true);
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
        $loader = new Loader(["{$this->folder}/.env"], new DotenvFactory(), false);

        // Set an environment variable.
        $loader->setEnvironmentVariable($this->key(), $this->value());

        // Clear the set environment variable.
        $loader->clearEnvironmentVariable($this->key());
        $this->assertSame(null, $loader->getEnvironmentVariable($this->key()));
        $this->assertSame(false, getenv($this->key()));
        $this->assertSame(false, isset($_ENV[$this->key()]));
        $this->assertSame(false, isset($_SERVER[$this->key()]));
        $this->assertSame([$this->key()], $loader->getEnvironmentVariableNames());
    }

    public function testImmutableLoaderCannotClearEnvironmentVars()
    {
        $loader = new Loader(["{$this->folder}/.env"], new DotenvFactory(), false);

        $loader->setImmutable(true);

        // Set an environment variable.
        $loader->setEnvironmentVariable($this->key(), $this->value());

        // Attempt to clear the environment variable, check that it fails.
        $loader->clearEnvironmentVariable($this->key());
        $this->assertSame($this->value(), $loader->getEnvironmentVariable($this->key()));
        $this->assertSame($this->value(), getenv($this->key()));
        $this->assertSame(true, isset($_ENV[$this->key()]));
        $this->assertSame(true, isset($_SERVER[$this->key()]));
        $this->assertSame([$this->key()], $loader->getEnvironmentVariableNames());
    }

    /**
     * @expectedException \Dotenv\Exception\InvalidPathException
     * @expectedExceptionMessage At least one environment file path must be provided.
     */
    public function testLoaderWithNoPaths()
    {
        (new Loader([], new DotenvFactory(), false))->load();
    }

    /**
     * @expectedException \Dotenv\Exception\InvalidPathException
     * @expectedExceptionMessage Unable to read any of the environment file(s) at
     */
    public function testLoaderWithBadPaths()
    {
        (new Loader(["{$this->folder}/BAD1", "{$this->folder}/BAD2"], new DotenvFactory(), false))->load();
    }

    public function testLoaderWithOneGoodPath()
    {
        $loader = (new Loader(["{$this->folder}/BAD1", "{$this->folder}/.env"], new DotenvFactory(), false));

        $this->assertCount(4, $loader->load());
    }

    public function testLoaderWithNoAdapters()
    {
        $loader = (new Loader([], new DotenvFactory([])));

        $content = "NVAR1=\"Hello\"\nNVAR2=\"World!\"\nNVAR3=\"{\$NVAR1} {\$NVAR2}\"\nNVAR4=\"\${NVAR1} \${NVAR2}\"";
        $expected = ['NVAR1' => 'Hello', 'NVAR2' => 'World!', 'NVAR3' => '{$NVAR1} {$NVAR2}', 'NVAR4' => '${NVAR1} ${NVAR2}'];

        $this->assertSame($expected, $loader->loadDirect($content));
    }

    public function testLoaderWithArrayAdapter()
    {
        $loader = (new Loader([], new DotenvFactory([new ArrayAdapter()])));

        $content = "NVAR1=\"Hello\"\nNVAR2=\"World!\"\nNVAR3=\"{\$NVAR1} {\$NVAR2}\"\nNVAR4=\"\${NVAR1} \${NVAR2}\"";
        $expected = ['NVAR1' => 'Hello', 'NVAR2' => 'World!', 'NVAR3' => '{$NVAR1} {$NVAR2}', 'NVAR4' => 'Hello World!'];

        $this->assertSame($expected, $loader->loadDirect($content));
    }
}
