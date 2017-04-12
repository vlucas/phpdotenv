<?php

namespace Dotenv;

use ArrayAccess;

/**
 * @author Nikola Posa <posa.nikola@gmail.com>
 */
class EnvironmentVariables implements ArrayAccess
{
    /**
     * @var bool
     */
    protected $immutable;

    protected function __construct($immutable)
    {
        $this->immutable = $immutable;
    }

    /**
     * Creates EnvironmentVariables instance.
     *
     * @return EnvironmentVariables
     */
    public static function create()
    {
        return new static(false);
    }

    /**
     * Creates immutable EnvironmentVariables instance.
     *
     * @return EnvironmentVariables
     */
    public static function createImmutable()
    {
        return new static(true);
    }

    /**
     * Tells whether environment variable has been defined.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return !is_null($this->get($name));
    }

    /**
     * Search the different places for environment variables and return first value found.
     *
     * @param string $name
     *
     * @return string|null
     */
    public function get($name)
    {
        switch (true) {
            case array_key_exists($name, $_ENV):
                return $_ENV[$name];
            case array_key_exists($name, $_SERVER):
                return $_SERVER[$name];
            default:
                $value = getenv($name);
                return $value === false ? null : $value; // switch getenv default to null
        }
    }

    /**
     * Set an environment variable.
     *
     * This is done using:
     * - putenv,
     * - $_ENV,
     * - $_SERVER.
     *
     * The environment variable value is stripped of single and double quotes.
     *
     * @param string      $name
     * @param string|null $value
     *
     * @return void
     */
    public function set($name, $value = null)
    {
        // Don't overwrite existing environment variables if we're immutable
        // Ruby's dotenv does this with `ENV[key] ||= value`.
        if ($this->immutable && $this->has($name)) {
            return;
        }

        // If PHP is running as an Apache module and an existing
        // Apache environment variable exists, overwrite it
        if (function_exists('apache_getenv') && function_exists('apache_setenv') && apache_getenv($name)) {
            apache_setenv($name, $value);
        }

        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    /**
     * Clear an environment variable.
     *
     * This is done using:
     * - putenv,
     * - unset($_ENV, $_SERVER).
     *
     * @param string $name
     *
     * @see setEnvironmentVariable()
     *
     * @return void
     */
    public function clear($name)
    {
        // Don't clear anything if we're immutable.
        if ($this->immutable) {
            return;
        }

        putenv($name);
        unset($_ENV[$name], $_SERVER[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $this->clear($offset);
    }
}
