<?php

namespace Dotenv;

use Dotenv\Environment\DotenvFactory;
use Dotenv\Environment\FactoryInterface;
use Dotenv\Exception\InvalidPathException;

/**
 * This is the dotenv class.
 *
 * It's responsible for loading a `.env` file in the given directory and
 * setting the environment vars.
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
     * @param string                                    $path
     * @param string|null                               $file
     * @param \Dotenv\Environment\FactoryInterface|null $envFactory
     *
     * @return \Dotenv\Dotenv
     */
    public static function create($path, $file = null, FactoryInterface $envFactory = null)
    {
        $loader = new Loader(
            self::getFilePath($path, $file),
            $envFactory ?: new DotenvFactory(),
            true
        );

        return new Dotenv($loader);
    }


    /**
     * Returns the full path to the file.
     *
     * @param string      $path
     * @param string|null $file
     *
     * @return string
     */
    private static function getFilePath($path, $file)
    {
        if (!is_string($file)) {
            $file = '.env';
        }

        $filePath = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$file;

        return $filePath;
    }

    /**
     * Load environment file in given directory.
     *
     * @throws \Dotenv\Exception\InvalidPathException|\Dotenv\Exception\InvalidFileException
     *
     * @return array
     */
    public function load()
    {
        return $this->loadData();
    }

    /**
     * Load environment file in given directory, suppress InvalidPathException.
     *
     * @return array
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
     * @return array
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
     * @return array
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
