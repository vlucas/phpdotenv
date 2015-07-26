<?php

use Dotenv\Loader;

class LoaderTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->fixturesFolder = dirname(__DIR__).'/fixtures/env';
        $this->fixturesFolderWrong = dirname(__DIR__).'/fixtures/env-wrong';

        // Generate a new, random keyVal.
        $this->keyVal(true);

        // Build an immutable and mutable loader for convenience.
        $this->mutableLoader = new Loader($this->fixturesFolder);
        $this->immutableLoader = new Loader($this->fixturesFolder, true);
    }

    // @see keyVal()
    protected $keyVal;

    /**
     * Generates a new key/value pair or returns the previous one.
     *
     * Since most of our functionality revolves around setting/retrieving keys
     * and values, we have this utility function to help generate new, unique
     * pairs.
     *
     * @param  boolean $reset
     *   If true, a new pair will be generated. If false, the last returned pair
     *   will be returned.
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
     * @see keyVal()
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
     * @see keyVal()
     *
     * @return string
     */
    protected function value() {
        $keyVal = $this->keyVal();
        return reset($keyVal);
    }

    /**
     * Tests that the mutable loader can clear environment variables.
     *
     * @return void
     */
    public function testMutableLoaderClearsEnvironmentVars()
    {
        // Set an environment variable.
        $this->mutableLoader->setEnvironmentVariable($this->key(), $this->value());

        // Clear the set environment variable.
        $this->mutableLoader->clearEnvironmentVariable($this->key());
        $this->assertSame(null, $this->mutableLoader->getEnvironmentVariable($this->key()));
        $this->assertSame(false, getenv($this->key()));
        $this->assertSame(false, isset($_ENV[$this->key()]));
        $this->assertSame(false, isset($_SERVER[$this->key()]));
    }

    /**
     * Tests the immutable loader cannot clear environment variables.
     *
     * @return void
     */
    public function testImmutableLoaderCannotClearEnvironmentVars()
    {
        // Set an environment variable.
        $this->immutableLoader->setEnvironmentVariable($this->key(), $this->value());

        // Attempt to clear the environment variable, check that it fails.
        $this->immutableLoader->clearEnvironmentVariable($this->key());
        $this->assertSame($this->value(), $this->immutableLoader->getEnvironmentVariable($this->key()));
        $this->assertSame($this->value(), getenv($this->key()));
        $this->assertSame(true, isset($_ENV[$this->key()]));
        $this->assertSame(true, isset($_SERVER[$this->key()]));
    }
}
