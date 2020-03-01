<?php

declare(strict_types=1);

namespace Dotenv\Store;

use Dotenv\Store\File\Paths;

final class StoreBuilder
{
    /**
     * The of default name.
     *
     * @var string[]
     */
    private const DEFAULT_NAME = '.env';

    /**
     * The paths to search within.
     *
     * @var string[]
     */
    private $paths;

    /**
     * The file names to search for.
     *
     * @var string[]
     */
    private $names;

    /**
     * Should file loading short circuit?
     *
     * @var bool
     */
    private $shortCircuit;

    /**
     * Create a new store builder instance.
     *
     * @param string[] $paths
     * @param string[] $names
     * @param bool     $shortCircuit
     *
     * @return void
     */
    private function __construct(array $paths = [], array $names = [], bool $shortCircuit = false)
    {
        $this->paths = $paths;
        $this->names = $names;
        $this->shortCircuit = $shortCircuit;
    }

    /**
     * Create a new store builder instance with no names.
     *
     * @return \Dotenv\Store\StoreBuilder
     */
    public static function createWithNoNames()
    {
        return new self();
    }

    /**
     * Create a new store builder instance with the default name.
     *
     * @return \Dotenv\Store\StoreBuilder
     */
    public static function createWithDefaultName()
    {
        return new self([], [self::DEFAULT_NAME]);
    }

    /**
     * Creates a store builder with the given path added.
     *
     * @param string $path
     *
     * @return \Dotenv\Store\StoreBuilder
     */
    public function addPath(string $path)
    {
        return new self(array_merge($this->paths, [$path]), $this->names, $this->shortCircuit);
    }

    /**
     * Creates a store builder with the given name added.
     *
     * @param string $name
     *
     * @return \Dotenv\Store\StoreBuilder
     */
    public function addName(string $name)
    {
        return new self($this->paths, array_merge($this->names, [$name]), $this->shortCircuit);
    }

    /**
     * Creates a store builder with short circuit mode enabled.
     *
     * @return \Dotenv\Store\StoreBuilder
     */
    public function shortCircuit()
    {
        return new self($this->paths, $this->names, true);
    }

    /**
     * Creates a new store instance.
     *
     * @return \Dotenv\Store\StoreInterface
     */
    public function make()
    {
        return new FileStore(
            Paths::filePaths($this->paths, $this->names),
            $this->shortCircuit
        );
    }
}
