<?php

namespace Dotenv;

use Dotenv\Environment\FactoryInterface;
use Dotenv\Exception\InvalidPathException;

/**
 * This is the loaded class.
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
     * The file path.
     *
     * @var string
     */
    protected $filePath;

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
     * @param string                               $filePath
     * @param \Dotenv\Environment\FactoryInterface $envFactory
     * @param bool                                 $immutable
     *
     * @return void
     */
    public function __construct($filePath, FactoryInterface $envFactory, $immutable = false)
    {
        $this->filePath = $filePath;
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
     * Load `.env` file in given directory.
     *
     * @throws \Dotenv\Exception\InvalidPathException|\Dotenv\Exception\InvalidFileException
     *
     * @return string[]
     */
    public function load()
    {
        return $this->processEntries(
            Lines::process(self::readLinesFromFile($this->filePath))
        );
    }

    /**
     * Read lines from the file, auto detecting line endings.
     *
     * @param string $filePath
     *
     * @throws \Dotenv\Exception\InvalidPathException
     *
     * @return string[]
     */
    private static function readLinesFromFile($filePath)
    {
        $autodetect = ini_get('auto_detect_line_endings');
        ini_set('auto_detect_line_endings', '1');
        $lines = @file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        ini_set('auto_detect_line_endings', $autodetect);

        if ($lines === false) {
            throw new InvalidPathException(
                sprintf('Unable to read the environment file at %s.', $filePath)
            );
        }

        return $lines;
    }

    /**
     * Process the environment variable entries.
     *
     * We'll fill out any nested variables, and acually set the variable using
     * the underlying environment variables instance.
     *
     * @param string[] $entries
     *
     * @return string[]
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
        if ($value === null || strpos($value, '$') === false) {
            return $value;
        }

        return preg_replace_callback(
            '/\${([a-zA-Z0-9_.]+)}/',
            [$this, 'nestedVariableResolver'],
            $value
        );
    }

    private function nestedVariableResolver(array $matches)
    {
        $nested = $this->getEnvironmentVariable($matches[1]);

        return $nested === null ? $matches[0] : $nested;
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
