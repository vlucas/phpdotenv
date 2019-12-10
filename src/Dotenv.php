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
     * Should file loading short circuit?
     *
     * @var bool
     */
    protected $shortCircuit;

    /**
     * Create a new dotenv instance.
     *
     * @param \Dotenv\Loader\LoaderInterface         $loader
     * @param \Dotenv\Repository\RepositoryInterface $repository
     * @param string[]                               $filePaths
     * @param bool                                   $shortCircuit
     *
     * @return void
     */
    public function __construct(LoaderInterface $loader, RepositoryInterface $repository, array $filePaths, $shortCircuit = true)
    {
        $this->loader = $loader;
        $this->repository = $repository;
        $this->filePaths = $filePaths;
        $this->shortCircuit = $shortCircuit;
    }

    /**
     * Create a new dotenv instance.
     *
     * @param \Dotenv\Repository\RepositoryInterface $repository
     * @param string|string[]                        $paths
     * @param string|string[]|null                   $names
     * @param bool                                   $shortCircuit
     *
     * @return \Dotenv\Dotenv
     */
    public static function create(RepositoryInterface $repository, $paths, $names = null, $shortCircuit = true)
    {
        $files = self::getFilePaths((array) $paths, (array) ($names ?: '.env'));

        return new self(new Loader(), $repository, $files, $shortCircuit);
    }

    /**
     * Create a new mutable dotenv instance with default repository.
     *
     * @param string|string[]      $paths
     * @param string|string[]|null $names
     * @param bool                 $shortCircuit
     *
     * @return \Dotenv\Dotenv
     */
    public static function createMutable($paths, $names = null, $shortCircuit = true)
    {
        $repository = RepositoryBuilder::create()->make();

        return self::create($repository, $paths, $names, $shortCircuit);
    }

    /**
     * Create a new immutable dotenv instance with default repository.
     *
     * @param string|string[]      $paths
     * @param string|string[]|null $names
     * @param bool                 $shortCircuit
     *
     * @return \Dotenv\Dotenv
     */
    public static function createImmutable($paths, $names = null, $shortCircuit = true)
    {
        $repository = RepositoryBuilder::create()->immutable()->make();

        return self::create($repository, $paths, $names, $shortCircuit);
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
        return $this->loader->load($this->repository, self::findAndRead($this->filePaths, $this->shortCircuit));
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
    private static function getFilePaths(array $paths, $names)
    {
        $files = [];

        foreach ($paths as $path) {
            foreach ($names as $name) {
                $files[] = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$name;
            }
        }

        return $files;
    }

    /**
     * Attempt to read the files in order.
     *
     * @param string[] $filePaths
     * @param bool     $shortCircuit
     *
     * @throws \Dotenv\Exception\InvalidPathException
     *
     * @return string
     */
    private static function findAndRead(array $filePaths, $shortCircuit)
    {
        if ($filePaths === []) {
            throw new InvalidPathException('At least one environment file path must be provided.');
        }

        $output = '';

        foreach ($filePaths as $filePath) {
            $content = self::readFromFile($filePath);
            if ($content->isDefined()) {
                $output .= $content->get()."\n";
                if ($shortCircuit) {
                    break;
                }
            }
        }

        if (!$output) {
            throw new InvalidPathException(
                sprintf('Unable to read any of the environment file(s) at [%s].', implode(', ', $filePaths))
            );
        }

        return $output;
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
