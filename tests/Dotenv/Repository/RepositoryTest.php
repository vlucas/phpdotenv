<?php

declare(strict_types=1);

namespace Dotenv\Tests\Repository;

use Dotenv\Dotenv;
use Dotenv\Repository\Adapter\ArrayAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Repository\RepositoryInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use TypeError;

final class RepositoryTest extends TestCase
{
    /**
     * @var array<string, string>|null
     */
    private $keyVal;

    /**
     * @before
     *
     * @return void
     */
    public function refreshKeyVal()
    {
        $this->keyVal(true);
    }

    /**
     * @return void
     */
    private function load()
    {
        Dotenv::createMutable(\dirname(\dirname(__DIR__)).'/fixtures/env')->load();
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
     * @return array<string, string>
     */
    private function keyVal(bool $reset = false)
    {
        if (!isset($this->keyVal) || $reset) {
            $this->keyVal = [\uniqid() => \uniqid()];
        }

        return $this->keyVal;
    }

    /**
     * Returns the key from keyVal(), without reset.
     *
     * @return string
     */
    private function key()
    {
        $keyVal = $this->keyVal();

        return (string) \key($keyVal);
    }

    /**
     * Returns the value from keyVal(), without reset.
     *
     * @return string
     */
    private function value()
    {
        $keyVal = $this->keyVal();

        /** @var string */
        return \reset($keyVal);
    }

    public function testRepositoryInstanceOf()
    {
        self::assertInstanceOf(RepositoryInterface::class, RepositoryBuilder::createWithNoAdapters()->make());
        self::assertInstanceOf(RepositoryInterface::class, RepositoryBuilder::createWithDefaultAdapters()->make());
    }

    public function testMutableLoaderClearsEnvironmentVars()
    {
        $repository = RepositoryBuilder::createWithDefaultAdapters()->make();

        // Set an environment variable.
        $repository->set($this->key(), $this->value());

        // Clear the set environment variable.
        $repository->clear($this->key());
        self::assertNull($repository->get($this->key()));
        self::assertFalse(\getenv($this->key()));
        self::assertFalse(isset($_ENV[$this->key()]));
        self::assertFalse(isset($_SERVER[$this->key()]));
    }

    public function testImmutableLoaderCannotClearExistingEnvironmentVars()
    {
        $this->load();

        $repository = RepositoryBuilder::createWithDefaultAdapters()->immutable()->make();

        // Pre-set an environment variable.
        RepositoryBuilder::createWithDefaultAdapters()->make()->set($this->key(), $this->value());

        // Attempt to clear the environment variable, check that it fails.
        $repository->clear($this->key());
        self::assertSame($this->value(), $repository->get($this->key()));
        self::assertTrue(isset($_ENV[$this->key()]));
        self::assertTrue(isset($_SERVER[$this->key()]));
    }

    public function testImmutableLoaderCanClearSetEnvironmentVars()
    {
        $this->load();

        $repository = RepositoryBuilder::createWithDefaultAdapters()->immutable()->make();

        // Set an environment variable.
        $repository->set($this->key(), $this->value());

        // Attempt to clear the environment variable, check that it works.
        $repository->clear($this->key());
        self::assertNull($repository->get($this->key()));
        self::assertFalse(\getenv($this->key()));
        self::assertFalse(isset($_ENV[$this->key()]));
        self::assertFalse(isset($_SERVER[$this->key()]));
    }

    public function testCheckingWhetherVariableExists()
    {
        $this->load();

        $repo = RepositoryBuilder::createWithDefaultAdapters()->make();

        self::assertTrue($repo->has('FOO'));
        self::assertFalse($repo->has('NON_EXISTING_VARIABLE'));
    }

    public function testHasWithBadVariable()
    {
        $repo = RepositoryBuilder::createWithDefaultAdapters()->make();

        $this->expectException(TypeError::class);

        $repo->has(null);
    }

    public function testGettingVariableByName()
    {
        $this->load();

        $repo = RepositoryBuilder::createWithDefaultAdapters()->make();

        self::assertSame('bar', $repo->get('FOO'));
    }

    public function testGettingNullVariable()
    {
        $repo = RepositoryBuilder::createWithDefaultAdapters()->make();

        $this->expectException(TypeError::class);

        $repo->get(null);
    }

    public function testGettingEmptyVariable()
    {
        $repo = RepositoryBuilder::createWithDefaultAdapters()->make();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected name to be a non-empty string.');

        $repo->get('');
    }

    public function testSettingVariable()
    {
        $this->load();

        $repo = RepositoryBuilder::createWithDefaultAdapters()->make();

        self::assertSame('bar', $repo->get('FOO'));
        $repo->set('FOO', 'new');
        self::assertSame('new', $repo->get('FOO'));
    }

    public function testSettingNullVariable()
    {
        $repo = RepositoryBuilder::createWithDefaultAdapters()->make();

        $this->expectException(TypeError::class);

        $repo->set(null, 'foo');
    }

    public function testSettingEmptyVariable()
    {
        $repo = RepositoryBuilder::createWithDefaultAdapters()->make();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected name to be a non-empty string.');

        $repo->set('', 'foo');
    }

    public function testClearingVariable()
    {
        $this->load();

        $repo = RepositoryBuilder::createWithDefaultAdapters()->make();

        self::assertTrue($repo->has('FOO'));
        $repo->clear('FOO');
        self::assertFalse($repo->has('FOO'));
    }

    public function testClearingVariableWithArrayAdapter()
    {
        $adapter = ArrayAdapter::create()->get();
        $repo = RepositoryBuilder::createWithNoAdapters()->addReader($adapter)->addWriter($adapter)->make();

        self::assertFalse($repo->has('FOO'));
        $repo->set('FOO', 'BAR');
        self::assertTrue($repo->has('FOO'));
        $repo->clear('FOO');
        self::assertFalse($repo->has('FOO'));
    }

    public function testClearingNullVariable()
    {
        $repo = RepositoryBuilder::createWithDefaultAdapters()->make();

        $this->expectException(TypeError::class);

        $repo->clear(null);
    }

    public function testClearingEmptyVariable()
    {
        $repo = RepositoryBuilder::createWithDefaultAdapters()->make();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected name to be a non-empty string.');

        $repo->clear('');
    }

    public function testCannotSetVariableOnImmutableInstance()
    {
        $this->load();

        $repo = RepositoryBuilder::createWithDefaultAdapters()->immutable()->make();

        self::assertSame('bar', $repo->get('FOO'));

        $repo->set('FOO', 'new');

        self::assertSame('bar', $repo->get('FOO'));
    }

    public function testCannotClearVariableOnImmutableInstance()
    {
        $this->load();

        $repo = RepositoryBuilder::createWithDefaultAdapters()->immutable()->make();

        $repo->clear('FOO');

        self::assertTrue($repo->has('FOO'));
    }

    public function testBuildWithBadReader()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected either an instance of ');

        RepositoryBuilder::createWithNoAdapters()->addReader('123');
    }

    public function testBuildWithBadWriter()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected either an instance of ');

        RepositoryBuilder::createWithNoAdapters()->addWriter('123');
    }

    public function testBuildWithBadAdapter()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected either an instance of ');

        RepositoryBuilder::createWithNoAdapters()->addAdapter('');
    }
}
