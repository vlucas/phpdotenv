<?php

use Dotenv\Loader;
use PHPUnit\Framework\TestCase;

class LoaderTest extends TestCase
{
    /**
     * @var \Dotenv\Loader
     */
    private $immutableLoader;

    /**
     * @var \Dotenv\Loader
     */
    private $mutableLoader;

    public function setUp()
    {
        $folder = dirname(__DIR__) . '/fixtures/env';

        // Generate a new, random keyVal.
        $this->keyVal(true);

        // Build an immutable and mutable loader for convenience.
        $this->mutableLoader = new Loader($folder);
        $this->immutableLoader = new Loader($folder, true);
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
     *   If true, a new pair will be generated. If false, the last returned pair
     *   will be returned.
     *
     * @return array
     */
    protected function keyVal($reset = false)
    {
        if (!isset($this->keyVal) || $reset) {
            $this->keyVal = array(uniqid() => uniqid());
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

    public function testMutableLoaderSetUnsetImmutable()
    {
        $immutable = $this->mutableLoader->getImmutable();

        // Set Immutable.
        $this->mutableLoader->setImmutable(!$immutable);
        $this->assertSame(!$immutable, $this->mutableLoader->getImmutable());
        $this->mutableLoader->setImmutable($immutable);
        $this->assertSame($immutable, $this->mutableLoader->getImmutable());
    }

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
        $this->assertTrue(is_array($this->mutableLoader->variableNames));
        $this->assertFalse(empty($this->mutableLoader->variableNames));

    }

    public function testImmutableLoaderSetUnsetImmutable()
    {
        $immutable = $this->immutableLoader->getImmutable();

        // Set Immutable.
        $this->immutableLoader->setImmutable(!$immutable);
        $this->assertSame(!$immutable, $this->immutableLoader->getImmutable());
        $this->immutableLoader->setImmutable($immutable);
        $this->assertSame($immutable, $this->immutableLoader->getImmutable());
    }

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
        $this->assertTrue(is_array($this->immutableLoader->variableNames));
        $this->assertFalse(empty($this->immutableLoader->variableNames));
    }
}
