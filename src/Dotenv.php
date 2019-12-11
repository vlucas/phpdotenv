<?php

namespace Dotenv;

use Dotenv\Exception\InvalidPathException;
use Dotenv\File\Paths;
use Dotenv\File\Reader;
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
        $files = Paths::filePaths((array) $paths, (array) ($names ?: '.env'));

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
     * Read and load environment file(s).
     *
     * @throws \Dotenv\Exception\InvalidPathException|\Dotenv\Exception\InvalidFileException
     *
     * @return array<string,string|null>
     */
    public function load()
    {
        if ($this->filePaths === []) {
            throw new InvalidPathException('At least one environment file path must be provided.');
        }

        return $this->tryLoad()->getOrCall(function () {
            throw new InvalidPathException(
                sprintf('Unable to read any of the environment file(s) at [%s].', implode(', ', $this->filePaths))
            );
        });
    }

    /**
     * Read and load environment file(s), silently failing if no files can be read.
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return array<string,string|null>
     */
    public function safeLoad()
    {
        return $this->tryLoad()->getOrElse([]);
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
     * Read and load environment file(s), returning an option.
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return \PhpOption\Option
     */
    private function tryLoad()
    {
        return self::aggregate(Reader::read($this->filePaths, $this->shortCircuit))->map(function ($content) {
            return $this->loader->load($this->repository, $content);
        });
    }

    /**
     * Aggregate the given raw file contents.
     *
     * @param array<string,string> $contents
     *
     * @return \PhpOption\Option
     */
    private static function aggregate(array $contents)
    {
        $output = '';

        foreach ($contents as $content) {
            $output .= $content."\n";
        }

        return Option::fromValue($output, '');
    }
}
