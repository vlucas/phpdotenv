<?php

namespace Dotenv\Repository;

use Dotenv\Repository\Adapter\ApacheAdapter;
use Dotenv\Repository\Adapter\AvailabilityInterface;
use Dotenv\Repository\Adapter\EnvConstAdapter;
use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\Adapter\ServerConstAdapter;

class RepositoryBuilder
{
    /**
     * The set of readers to use.
     *
     * @var \Dotenv\Repository\Adapter\ReaderInterface[]|null
     */
    private $readers;

    /**
     * The set of writers to use.
     *
     * @var \Dotenv\Repository\Adapter\WriterInterface[]|null
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
     * @param \Dotenv\Repository\Adapter\ReaderInterface[]|null $readers
     * @param \Dotenv\Repository\Adapter\WriterInterface[]|null $writers
     * @param bool                                              $immutable
     *
     * @return void
     */
    private function __construct(array $readers = null, array $writers = null, $immutable = false)
    {
        $this->readers = $readers;
        $this->writers = $writers;
        $this->immutable = $immutable;
    }

    /**
     * Create a new repository builder instance.
     *
     * @return \Dotenv\Repository\RepositoryBuilder
     */
    public static function create()
    {
        return new self();
    }

    /**
     * Creates a repository builder with the given readers.
     *
     * @param \Dotenv\Repository\Adapter\ReaderInterface[]|null $readers
     *
     * @return \Dotenv\Repository\RepositoryBuilder
     */
    public function withReaders(array $readers = null)
    {
        $readers = $readers === null ? null : self::filterByAvailability($readers);

        return new self($readers, $this->writers, $this->immutable);
    }

    /**
     * Creates a repository builder with the given writers.
     *
     * @param \Dotenv\Repository\Adapter\WriterInterface[]|null $writers
     *
     * @return \Dotenv\Repository\RepositoryBuilder
     */
    public function withWriters(array $writers = null)
    {
        $writers = $writers === null ? null : self::filterByAvailability($writers);

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
        if ($this->readers === null || $this->writers === null) {
            $defaults = self::defaultAdapters();
        }

        return new AdapterRepository(
            $this->readers === null ? $defaults : $this->readers,
            $this->writers === null ? $defaults : $this->writers,
            $this->immutable
        );
    }

    /**
     * Return the array of default adapters.
     *
     * @return \Dotenv\Repository\Adapter\AvailabilityInterface[]
     */
    private static function defaultAdapters()
    {
        return self::filterByAvailability([
            new ApacheAdapter(),
            new EnvConstAdapter(),
            new ServerConstAdapter(),
            new PutenvAdapter(),
        ]);
    }

    /**
     * Filter an array of adapters to only those that are supported.
     *
     * @param \Dotenv\Repository\Adapter\AvailabilityInterface[] $adapters
     *
     * @return \Dotenv\Repository\Adapter\AvailabilityInterface[]
     */
    private static function filterByAvailability(array $adapters)
    {
        return array_filter($adapters, function (AvailabilityInterface $adapter) {
            return $adapter->isSupported();
        });
    }
}
