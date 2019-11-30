<?php

namespace Dotenv;

use Dotenv\Exception\InvalidPathException;
use Dotenv\Loader\Loader;
use Dotenv\Loader\LoaderInterface;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Repository\RepositoryInterface;
use PhpOption\Option;

class Dotenv
{
    /**
     * The loader instance.
     *
     * @var \Dotenv\Loader\LoaderInterface
     */
    protected $loader;

    /**
     * The repository instance.
     *
     * @var \Dotenv\Repository\RepositoryInterface
     */
    protected $repository;

    /**
     * The file paths.
     *
     * @var string[]
     */
    protected $filePaths;

    /**
     * Create a new dotenv instance.
     *
     * @param \Dotenv\Loader\LoaderInterface         $loader
     * @param \Dotenv\Repository\RepositoryInterface $repository
     * @param string[]                               $filePaths
     *
     * @return void
     */
    public function __construct(LoaderInterface $loader, RepositoryInterface $repository, array $filePaths)
    {
        $this->loader = $loader;
        $this->repository = $repository;
        $this->filePaths = $filePaths;
    }

    /**
     * Create a new dotenv instance.
     *
     * @param \Dotenv\Repository\RepositoryInterface $repository
     * @param string|string[]                        $paths
     * @param string|null                            $file
     *
     * @return \Dotenv\Dotenv
     */
    public static function create(RepositoryInterface $repository, $paths, $file = null)
    {
        return new self(new Loader(), $repository, self::getFilePaths((array) $paths, $file ?: '.env'));
    }

    /**
     * Create a new mutable dotenv instance with default repository.
     *
     * @param string|string[] $paths
     * @param string|null     $file
     *
     * @return \Dotenv\Dotenv
     */
    public static function createMutable($paths, $file = null)
    {
        $repository = RepositoryBuilder::create()->make();

        return self::create($repository, $paths, $file);
    }

    /**
     * Create a new mutable dotenv instance with default repository.
     *
     * @param string|string[] $paths
     * @param string|null     $file
     *
     * @return \Dotenv\Dotenv
     */
    public static function createImmutable($paths, $file = null)
    {
        $repository = RepositoryBuilder::create()->immutable()->make();

        return self::create($repository, $paths, $file);
    }

    /**
     * Load environment file in given directory.
     *
     * @throws \Dotenv\Exception\InvalidPathException|\Dotenv\Exception\InvalidFileException
     *
     * @return array<string|null>
     */
    public function load()
    {
        return $this->loader->load($this->repository, self::findAndRead($this->filePaths));
    }

    /**
     * Load environment file in given directory, silently failing if it doesn't exist.
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return array<string|null>
     */
    public function safeLoad()
    {
        try {
            return $this->load();
        } catch (InvalidPathException $e) {
            // suppressing exception
            return [];
        }
    }

    /**
     * Required ensures that the specified variables exist, and returns a new validator object.
     *
     * @param string|string[] $variables
     *
     * @return \Dotenv\Validator
     */
    public function required($variables)
    {
        return new Validator($this->repository, (array) $variables);
    }

    /**
     * Returns a new validator object that won't check if the specified variables exist.
     *
     * @param string|string[] $variables
     *
     * @return \Dotenv\Validator
     */
    public function ifPresent($variables)
    {
        return new Validator($this->repository, (array) $variables, false);
    }

    /**
     * Returns the full paths to the files.
     *
     * @param string[] $paths
     * @param string   $file
     *
     * @return string[]
     */
    private static function getFilePaths(array $paths, $file)
    {
        return array_map(function ($path) use ($file) {
            return rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$file;
        }, $paths);
    }

    /**
     * Attempt to read the files in order.
     *
     * @param string[] $filePaths
     *
     * @throws \Dotenv\Exception\InvalidPathException
     *
     * @return string[]
     */
    private static function findAndRead(array $filePaths)
    {
        if ($filePaths === []) {
            throw new InvalidPathException('At least one environment file path must be provided.');
        }

        foreach ($filePaths as $filePath) {
            $lines = self::readFromFile($filePath);
            if ($lines->isDefined()) {
                return $lines->get();
            }
        }

        throw new InvalidPathException(
            sprintf('Unable to read any of the environment file(s) at [%s].', implode(', ', $filePaths))
        );
    }

    /**
     * Read the given file.
     *
     * @param string $filePath
     *
     * @return \PhpOption\Option
     */
    private static function readFromFile($filePath)
    {
        $content = @file_get_contents($filePath);

        return Option::fromValue($content, false);
    }
}
