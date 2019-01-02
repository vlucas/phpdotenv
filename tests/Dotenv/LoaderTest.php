<?php

use Dotenv\Environment\DotenvFactory;
use Dotenv\Loader;
use PHPUnit\Framework\TestCase;

class LoaderTest extends TestCase
{
    /**
     * @var \Dotenv\Loader
     */
    private $loader;

    public function setUp()
    {
        $folder = dirname(__DIR__).'/fixtures/env';
        $this->keyVal(true);
        $this->loader = new Loader($folder, new DotenvFactory(), false);
    }

    protected $keyVal;

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
        // Set an environment variable.
        $this->loader->setEnvironmentVariable($this->key(), $this->value());

        // Clear the set environment variable.
        $this->loader->clearEnvironmentVariable($this->key());
        $this->assertSame(null, $this->loader->getEnvironmentVariable($this->key()));
        $this->assertSame(false, getenv($this->key()));
        $this->assertSame(false, isset($_ENV[$this->key()]));
        $this->assertSame(false, isset($_SERVER[$this->key()]));
        $this->assertSame([$this->key()], $this->loader->getEnvironmentVariableNames());
    }

    public function testImmutableLoaderCannotClearEnvironmentVars()
    {
        $this->loader->setImmutable(true);

        // Set an environment variable.
        $this->loader->setEnvironmentVariable($this->key(), $this->value());

        // Attempt to clear the environment variable, check that it fails.
        $this->loader->clearEnvironmentVariable($this->key());
        $this->assertSame($this->value(), $this->loader->getEnvironmentVariable($this->key()));
        $this->assertSame($this->value(), getenv($this->key()));
        $this->assertSame(true, isset($_ENV[$this->key()]));
        $this->assertSame(true, isset($_SERVER[$this->key()]));
        $this->assertSame([$this->key()], $this->loader->getEnvironmentVariableNames());
    }
}
