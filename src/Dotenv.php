<?php

namespace Dotenv;

use Dotenv\Variable\Loader\EnvLoader;
use Dotenv\Variable\Loader\PhpLoader;
use Dotenv\Variable\LoadsVariables;
use Dotenv\Variable\Variable;
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
     * @var VariableFactory
     */
    protected $variableFactory;

    /**
     * The collection of objects that can load variables.
     *
     * @var array[string]LoadsVariables `['.supported.extension' => $loaderObject]`
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
    }

    /**
     * Register an object that can load variables.
     *
     * @param LoadsVariables $loader
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
     * @param string $path path to directory holding environment config file
     * @param string|null $file
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
     * Set a variable using:
     * - putenv
     * - $_ENV
     * - $_SERVER
     *
     * @param string $name
     * @param string $value
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
     * Require specified ENV vars to be present, or throw a `RuntimeException`.
     *
     * You can also pass through an set of allowed values for the environment variable.
     *
     * @param  mixed     $variables the name of the environment variable or an array of names
     * @param  string[]  $allowedValues
     * @throws \RuntimeException
     * @return $this     (or throws exception on error)
     */
    public function required($variables, array $allowedValues = array())
    {
        $variables = (array) $variables;

        $missingVariables = array();
        foreach ($variables as $variableName) {
            try {
                $variable = new Variable($variableName);
                $variable->required($allowedValues);
            } catch (\InvalidArgumentException $e) {
                $missingVariables[] = $e->getMessage();
            }
        }

        if ($missingVariables) {
            throw new \RuntimeException(sprintf(
                "Required environment variable missing, or value not allowed: '%s'",
                implode("', '", $missingVariables)
            ));
        }

        return $this;
    }

    /**
     * Search the different places for environment variables and return first value found.
     *
     * @param string $name
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
     * @return string path to the file
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
     * @throws \InvalidArgumentException
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
     * @return string
     * @throws \InvalidArgumentException
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
     * @return \Dotenv\Variable\LoadsVariables
     * @throws \InvalidArgumentException
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
