<?php

namespace Dotenv\Store;

use Dotenv\Store\File\Paths;

class StoreBuilder
{
    /**
     * The paths to search within.
     *
     * @var string[]
     */
    private $paths;

    /**
     * The file names to search for.
     *
     * @var string[]|null
     */
    private $names;

    /**
     * Should file loading short circuit?
     *
     * @var bool
     */
    protected $shortCircuit;

    /**
     * Create a new store builder instance.
     *
     * @param string[]      $paths
     * @param string[]|null $names
     * @param bool          $shortCircuit
     *
     * @return void
     */
    private function __construct(array $paths = [], array $names = null, $shortCircuit = false)
    {
        $this->paths = $paths;
        $this->names = $names;
        $this->shortCircuit = $shortCircuit;
    }

    /**
     * Create a new store builder instance.
     *
     * @return \Dotenv\Store\StoreBuilder
     */
    public static function create()
    {
        return new self();
    }

    /**
     * Creates a store builder with the given paths.
     *
     * @param string|string[] $paths
     *
     * @return \Dotenv\Store\StoreBuilder
     */
    public function withPaths($paths)
    {
        return new self((array) $paths, $this->names, $this->shortCircuit);
    }

    /**
     * Creates a store builder with the given names.
     *
     * @param string|string[]|null $names
     *
     * @return \Dotenv\Store\StoreBuilder
     */
    public function withNames($names = null)
    {
        return new self($this->paths, $names === null ? null : (array) $names, $this->shortCircuit);
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
            Paths::filePaths($this->paths, $this->names === null ? ['.env'] : $this->names),
            $this->shortCircuit
        );
    }
}
