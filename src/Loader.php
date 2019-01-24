<?php

namespace Dotenv;

use Dotenv\Environment\FactoryInterface;
use Dotenv\Exception\InvalidPathException;
use Dotenv\Regex\Regex;
use PhpOption\Option;

/**
 * This is the loader class.
 *
 * It's responsible for loading variables by reading a file from disk and:
 * - stripping comments beginning with a `#`,
 * - parsing lines that look shell variable setters, e.g `export key = value`, `key="value"`.
 * - multiline variable look always start with a " and end with it, e.g: `key="value
 *                                                                             value"`
 */
class Loader
{
    /**
     * The file paths.
     *
     * @var string[]
     */
    protected $filePaths;

    /**
     * The environment factory instance.
     *
     * @var \Dotenv\Environment\FactoryInterface
     */
    protected $envFactory;

    /**
     * The environment variables instance.
     *
     * @var \Dotenv\Environment\VariablesInterface
     */
    protected $envVariables;

    /**
     * The list of environment variables declared inside the 'env' file.
     *
     * @var string[]
     */
    protected $variableNames = [];

    /**
     * Create a new loader instance.
     *
     * @param string[]                             $filePaths
     * @param \Dotenv\Environment\FactoryInterface $envFactory
     * @param bool                                 $immutable
     *
     * @return void
     */
    public function __construct(array $filePaths, FactoryInterface $envFactory, $immutable = false)
    {
        $this->filePaths = $filePaths;
        $this->envFactory = $envFactory;
        $this->setImmutable($immutable);
    }

    /**
     * Set immutable value.
     *
     * @param bool $immutable
     *
     * @return $this
     */
    public function setImmutable($immutable = false)
    {
        $this->envVariables = $immutable
            ? $this->envFactory->createImmutable()
            : $this->envFactory->create();

        return $this;
    }

    /**
     * Load the environment file from disk.
     *
     * @throws \Dotenv\Exception\InvalidPathException|\Dotenv\Exception\InvalidFileException
     *
     * @return array<string|null>
     */
    public function load()
    {
        return $this->loadDirect(
            self::findAndRead($this->filePaths)
        );
    }

    /**
     * Directly load the given string.
     *
     * @param string $content
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return array<string|null>
     */
    public function loadDirect($content)
    {
        return $this->processEntries(
            Lines::process(preg_split("/(\r\n|\n|\r)/", $content))
        );
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

    /**
     * Process the environment variable entries.
     *
     * We'll fill out any nested variables, and acually set the variable using
     * the underlying environment variables instance.
     *
     * @param string[] $entries
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return array<string|null>
     */
    private function processEntries(array $entries)
    {
        $vars = [];

        foreach ($entries as $entry) {
            list($name, $value) = Parser::parse($entry);
            $vars[$name] = $this->resolveNestedVariables($value);
            $this->setEnvironmentVariable($name, $vars[$name]);
        }

        return $vars;
    }

    /**
     * Resolve the nested variables.
     *
     * Look for ${varname} patterns in the variable value and replace with an
     * existing environment variable.
     *
     * @param string|null $value
     *
     * @return string|null
     */
    private function resolveNestedVariables($value = null)
    {
        return Option::fromValue($value)
            ->filter(function ($str) {
                return strpos($str, '$') !== false;
            })
            ->flatMap(function ($str) {
                return Regex::replaceCallback(
                    '/\${([a-zA-Z0-9_.]+)}/',
                    function (array $matches) {
                        return Option::fromValue($this->getEnvironmentVariable($matches[1]))
                            ->getOrElse($matches[0]);
                    },
                    $str
                )->success();
            })
            ->getOrElse($value);
    }

    /**
     * Search the different places for environment variables and return first value found.
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getEnvironmentVariable($name)
    {
        return $this->envVariables->get($name);
    }

    /**
     * Set an environment variable.
     *
     * @param string      $name
     * @param string|null $value
     *
     * @return void
     */
    public function setEnvironmentVariable($name, $value = null)
    {
        $this->variableNames[] = $name;
        $this->envVariables->set($name, $value);
    }

    /**
     * Clear an environment variable.
     *
     * This method only expects names in normal form.
     *
     * @param string $name
     *
     * @return void
     */
    public function clearEnvironmentVariable($name)
    {
        $this->envVariables->clear($name);
    }

    /**
     * Get the list of environment variables names.
     *
     * @return string[]
     */
    public function getEnvironmentVariableNames()
    {
        return $this->variableNames;
    }
}
