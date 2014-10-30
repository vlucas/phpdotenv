<?php

namespace Dotenv;

use Dotenv\Variable\Loader\EnvLoader;
use Dotenv\Variable\Loader\PhpLoader;
use Dotenv\Variable\LoadsVariables;
use Dotenv\Variable\Parser;
use Dotenv\Variable\Variable;

/**
 * Dotenv
 *
 * Loads a `.env` file in the given directory and sets the environment vars
 */
class Dotenv
{
    /** @var Parser */
    protected $parser;
    /** @var array[string]LoadsVariables `['supported.extension' => 'Fully\Qualified\ClassName']` */
    protected $variableLoaders = array();

    public function __construct()
    {
        $this->registerLoader(new EnvLoader());
        $this->registerLoader(new PhpLoader());
    }

    public function registerLoader(LoadsVariables $loader)
    {
        $extension = $loader->extension();
        $this->variableLoaders[$extension] = $loader;
    }

    /**
     * @param string $file base name
     * @return \Dotenv\Variable\LoadsVariables
     * @throws \InvalidArgumentException
     */
    protected function resolveLoader($file)
    {
        $file = $file ?: '.env';

        foreach ($this->variableLoaders as $extension => $loader) {
            if ($file == $extension || substr($file, strlen($extension) *-1) == $extension) {
                return $loader;
            }
        }
        throw new \InvalidArgumentException("No loader found for file $file");
    }

    /**
     * Load `.env` file in given directory, leaving any existing environment variables alone.
     * @param string $path path to directory holding environment config file
     * @param string $file
     * @return $this
     */
    public function load($path, $file = '.env')
    {
        $loader = $this->resolveLoader($file);
        $loader->loadFromFile($path, $file, $immutable = true);
        return $this;
    }

    /**
     * Load `.env` file in given directory, overwriting any existing environment variables.
     * @param string $path path to directory holding environment config file
     * @param string $file
     * @return $this
     */
    public function overload($path, $file = '.env')
    {
        $loader = $this->resolveLoader($file);
        $loader->loadFromFile($path, $file, $immutable = false);
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
     * Require specified ENV vars to be present, or throw Exception.
     * You can also pass through an set of allowed values for the environment variable.
     *
     * @throws \RuntimeException
     * @param  mixed     $variables the name of the environment variable or an array of names
     * @param  string[]  $allowedValues
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
     * @param $name
     * @return string
     */
    public function get($name)
    {
        $variable = new Variable($name);
        return $variable->get();
    }
}
