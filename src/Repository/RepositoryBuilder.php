<?php

declare(strict_types=1);

namespace Dotenv\Repository;

use Dotenv\Repository\Adapter\AdapterInterface;
use Dotenv\Repository\Adapter\ApacheAdapter;
use Dotenv\Repository\Adapter\AvailabilityInterface;
use Dotenv\Repository\Adapter\EnvConstAdapter;
use Dotenv\Repository\Adapter\ImmutableWriter;
use Dotenv\Repository\Adapter\MultiReader;
use Dotenv\Repository\Adapter\MultiWriter;
use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\Adapter\ReaderInterface;
use Dotenv\Repository\Adapter\ServerConstAdapter;
use Dotenv\Repository\Adapter\WriterInterface;
use InvalidArgumentException;
use PhpOption\Some;
use ReflectionClass;

final class RepositoryBuilder
{
    /**
     * The set of default adapters.
     *
     * @var string[]
     */
    private const DEFAULT_ADAPTERS = [
        ApacheAdapter::class,
        ServerConstAdapter::class,
        EnvConstAdapter::class,
        PutenvAdapter::class,
    ];

    /**
     * The set of readers to use.
     *
     * @var \Dotenv\Repository\Adapter\ReaderInterface[]
     */
    private $readers;

    /**
     * The set of writers to use.
     *
     * @var \Dotenv\Repository\Adapter\WriterInterface[]
     */
    private $writers;

    /**
     * Are we immutable?
     *
     * @var bool
     */
    private $immutable;

    /**
     * Create a new repository builder instance.
     *
     * @param \Dotenv\Repository\Adapter\ReaderInterface[] $readers
     * @param \Dotenv\Repository\Adapter\WriterInterface[] $writers
     * @param bool                                         $immutable
     *
     * @return void
     */
    private function __construct(array $readers = [], array $writers = [], $immutable = false)
    {
        $this->readers = $readers;
        $this->writers = $writers;
        $this->immutable = $immutable;
    }

    /**
     * Create a new repository builder instance with no adapters added.
     *
     * @return \Dotenv\Repository\RepositoryBuilder
     */
    public static function createWithNoAdapters()
    {
        return new self();
    }

    /**
     * Create a new repository builder instance with the default adapters added.
     *
     * @return \Dotenv\Repository\RepositoryBuilder
     */
    public static function createWithDefaultAdapters()
    {
        $adapters = iterator_to_array(self::defaultAdapters());

        return new self($adapters, $adapters);
    }

    /**
     * Return the array of default adapters.
     *
     * @return \Generator<\Dotenv\Repository\Adapter\AdapterInterface>
     */
    private static function defaultAdapters()
    {
        foreach (self::DEFAULT_ADAPTERS as $adapter) {
            $instance = $adapter::create();
            if ($instance->isDefined()) {
                yield $instance->get();
            }
        }
    }

    /**
     * Determine if the given name if of an adapaterclass.
     *
     * @param string $name
     *
     * @return bool
     */
    private static function isAnAdapterClass(string $name)
    {
        if (!class_exists($name)) {
            return false;
        }

        return (new ReflectionClass($name))->implementsInterface(AdapterInterface::class);
    }

    /**
     * Creates a repository builder with the given reader added.
     *
     * Accepts either a reader instance, or a class-string for an adapter. If
     * the adapter is not supported, then we silently skip adding it.
     *
     * @param \Dotenv\Repository\Adapter\ReaderInterface|string $reader
     *
     * @throws \InvalidArgumentException
     *
     * @return \Dotenv\Repository\RepositoryBuilder
     */
    public function addReader($reader)
    {
        if (!(is_string($reader) && self::isAnAdapterClass($reader)) && !($reader instanceof ReaderInterface)) {
            throw new InvalidArgumentException(
                sprintf('Expected either an instance of %s or a class-string implementing %s',
                    ReaderInterface::class,
                    AdapterInterface::class
                )
            );
        }

        $optional = Some::create($reader)->flatMap(function ($reader) {
            return is_string($reader) ? $reader::create() : Some::create($reader);
        });

        $readers = array_merge($this->readers, iterator_to_array($optional));

        return new self($readers, $this->writers, $this->immutable);
    }

    /**
     * Creates a repository builder with the given writer added.
     *
     * Accepts either a writer instance, or a class-string for an adapter. If
     * the adapter is not supported, then we silently skip adding it.
     *
     * @param \Dotenv\Repository\Adapter\WriterInterface|string $writer
     *
     * @throws \InvalidArgumentException
     *
     * @return \Dotenv\Repository\RepositoryBuilder
     */
    public function addWriter($writer)
    {
        if (!(is_string($writer) && self::isAnAdapterClass($writer)) && !($writer instanceof WriterInterface)) {
            throw new InvalidArgumentException(
                sprintf('Expected either an instance of %s or a class-string implementing %s',
                    WriterInterface::class,
                    AdapterInterface::class
                )
            );
        }

        $optional = Some::create($writer)->flatMap(function ($writer) {
            return is_string($writer) ? $writer::create() : Some::create($writer);
        });

        $writers = array_merge($this->writers, iterator_to_array($optional));

        return new self($this->readers, $writers, $this->immutable);
    }

    /**
     * Creates a repository builder with mutability enabled.
     *
     * @return \Dotenv\Repository\RepositoryBuilder
     */
    public function immutable()
    {
        return new self($this->readers, $this->writers, true);
    }

    /**
     * Creates a new repository instance.
     *
     * @return \Dotenv\Repository\RepositoryInterface
     */
    public function make()
    {
        $reader = new MultiReader($this->readers);
        $writer = new MultiWriter($this->writers);

        return new AdapterRepository(
            $reader,
            $this->immutable ? new ImmutableWriter($reader, $writer) : $writer
        );
    }
}
