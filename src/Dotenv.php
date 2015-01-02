<?php

namespace Dotenv;

use Dotenv\Variable\Loader\EnvLoader;
use Dotenv\Variable\Loader\JsonLoader;
use Dotenv\Variable\Loader\PhpLoader;
use Dotenv\Variable\LoadsVariables;
use Dotenv\Variable\Variable;
use Dotenv\Variable\VariableAssertion;
use Dotenv\Variable\VariableFactory;

/**
 * Dotenv
 *
 * Sets environment variables from an env file in a directory.
 */
class Dotenv
{
    /**
     * The factory used to create variables.
     *
     * @var \Dotenv\Variable\VariableFactory
     */
    protected $variableFactory;

    /**
     * The collection of objects that can load variables.
     *
     * @var \Dotenv\Variable\LoadsVariables[string]
     */
    protected $variableLoaders = array();

    /**
     * Create a new Dotenv instance.
     */
    public function __construct()
    {
        $this->variableFactory = new VariableFactory();
        $this->registerLoader(new EnvLoader());
        $this->registerLoader(new PhpLoader());
        $this->registerLoader(new JsonLoader());
    }

    /**
     * Register an object that can load variables.
     *
     * @param \Dotenv\Variable\LoadsVariables $loader
     *
     * @return $this
     */
    public function registerLoader(LoadsVariables $loader)
    {
        $extension = $loader->extension();
        $this->variableLoaders[$extension] = $loader;

        return $this;
    }

    /**
     * Load `.env` file in given directory, leaving any existing environment variables alone.
     *
     * @param string      $path path to directory holding environment config file
     * @param string|null $file
     *
     * @return $this
     */
    public function load($path, $file = null)
    {
        $file = $this->getFilePath($path, $file);
        $loader = $this->resolveLoader($file);
        $loader->loadFromFile($this->variableFactory, $file, $immutable = true);

        return $this;
    }

    /**
     * Load `.env` file in given directory, overwriting any existing environment variables.
     *
     * @param string $path path to directory holding environment config file
     * @param string $file
     *
     * @return $this
     */
    public function overload($path, $file = null)
    {
        $file = $this->getFilePath($path, $file);
        $loader = $this->resolveLoader($file);
        $loader->loadFromFile($this->variableFactory, $file, $immutable = false);

        return $this;
    }

    /**
     * Set an environment variable.
     *
     * @param string $name
     * @param string $value
     *
     * @return $this
     */
    public function put($name, $value)
    {
        $variable = new Variable($name);
        $variable->prepareValue($value);
        $variable->commit($immutable = false);

        return $this;
    }

    /**
     * Run assertions against one or more variables.
     *
     * @param  string|string[]   $variables
     * @return VariableAssertion
     */
    public function exists($variables)
    {
        $variables = (array) $variables;
        array_walk($variables, function (&$value) {
            $value = new Variable($value);
        });

        return new VariableAssertion($variables);
    }

    /**
     * Search the different places for environment variables and return first value found.
     *
     * @param string $name
     *
     * @return string
     */
    public function get($name)
    {
        $variable = new Variable($name);

        return $variable->get();
    }

    /**
     * Returns the full path to the file ensuring that it's readable.
     *
     * @param string $path
     * @param string $file
     *
     * @return string
     */
    private function getFilePath($path, $file)
    {
        $file = $this->findDefaultFileIfNeeded($path, $file);
        $filePath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;
        $this->ensureFileIsReadable($filePath);

        return $filePath;
    }

    /**
     * Ensures the given filePath is readable.
     *
     * @param $filePath
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    private function ensureFileIsReadable($filePath)
    {
        if (!is_readable($filePath) || !is_file($filePath)) {
            throw new \InvalidArgumentException(sprintf(
                "Dotenv: Environment file .env not found or not readable. " .
                "Create file with your environment settings at %s",
                $filePath
            ));
        }
    }

    /**
     * If no filename is given, look for a default file to use.
     *
     * @param string $path
     * @param string $file
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    private function findDefaultFileIfNeeded($path, $file)
    {
        if ($file) {
            return $file;
        }

        $defaults = array_keys($this->variableLoaders);
        foreach ($defaults as $default) {
            if (file_exists($path . DIRECTORY_SEPARATOR . $default)) {
                return $default;
            }
        }

        throw new \InvalidArgumentException('No environment config found in '.$path);
    }

    /**
     * Given the basename of a file, return a variable loader that matches the extension.
     * If no loader is found, an `InvalidArgumentException` exception is thrown.
     *
     * @param string $file base name
     *
     * @throws \InvalidArgumentException
     *
     * @return \Dotenv\Variable\LoadsVariables
     */
    protected function resolveLoader($file)
    {
        foreach ($this->variableLoaders as $extension => $loader) {
            if ($file == $extension || substr($file, strlen($extension) *-1) == $extension) {
                return $loader;
            }
        }
        throw new \InvalidArgumentException("No loader found for file $file");
    }
}
