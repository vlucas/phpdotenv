<?php

namespace Dotenv;

use Dotenv\Environment\DotenvFactory;
use Dotenv\Environment\FactoryInterface;
use Dotenv\Exception\InvalidPathException;

/**
 * This is the dotenv class.
 *
 * It's responsible for loading a `.env` file in the given directory and
 * setting the environment variables.
 */
class Dotenv
{
    /**
     * The loader instance.
     *
     * @var \Dotenv\Loader
     */
    protected $loader;

    /**
     * Create a new dotenv instance.
     *
     * @param \Dotenv\Loader $loader
     *
     * @return void
     */
    public function __construct(Loader $loader)
    {
        $this->loader = $loader;
    }

    /**
     * Create a new dotenv instance.
     *
     * @param string|string[]                           $paths
     * @param string|null                               $file
     * @param \Dotenv\Environment\FactoryInterface|null $envFactory
     *
     * @return \Dotenv\Dotenv
     */
    public static function create($paths, $file = null, FactoryInterface $envFactory = null)
    {
        $loader = new Loader(
            self::getFilePaths((array) $paths, $file ?: '.env'),
            $envFactory ?: new DotenvFactory(),
            true
        );

        return new self($loader);
    }

    /**
     * Returns the full paths to the files.
     *
     * @param string[] $paths
     * @param string   $file
     *
     * @return string
     */
    private static function getFilePaths(array $paths, $file)
    {
        return array_map(function ($path) use ($file) {
            return rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$file;
        }, $paths);
    }

    /**
     * Load environment file in given directory.
     *
     * @throws \Dotenv\Exception\InvalidPathException|\Dotenv\Exception\InvalidFileException
     *
     * @return string[]
     */
    public function load()
    {
        return $this->loadData();
    }

    /**
     * Load environment file in given directory, silently failing if it doesn't exist.
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return string[]
     */
    public function safeLoad()
    {
        try {
            return $this->loadData();
        } catch (InvalidPathException $e) {
            // suppressing exception
            return [];
        }
    }

    /**
     * Load environment file in given directory.
     *
     * @throws \Dotenv\Exception\InvalidPathException|\Dotenv\Exception\InvalidFileException
     *
     * @return string[]
     */
    public function overload()
    {
        return $this->loadData(true);
    }

    /**
     * Actually load the data.
     *
     * @param bool $overload
     *
     * @throws \Dotenv\Exception\InvalidPathException|\Dotenv\Exception\InvalidFileException
     *
     * @return string[]
     */
    protected function loadData($overload = false)
    {
        return $this->loader->setImmutable(!$overload)->load();
    }

    /**
     * Required ensures that the specified variables exist, and returns a new validator object.
     *
     * @param string|string[] $variable
     *
     * @return \Dotenv\Validator
     */
    public function required($variable)
    {
        return new Validator((array) $variable, $this->loader);
    }

    /**
     * Get the list of environment variables declared inside the 'env' file.
     *
     * @return string[]
     */
    public function getEnvironmentVariableNames()
    {
        return $this->loader->getEnvironmentVariableNames();
    }
}
