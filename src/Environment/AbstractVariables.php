<?php

namespace Dotenv\Environment;

use Dotenv\Environment\Adapter\ArrayAdapter;
use InvalidArgumentException;

/**
 * This is the abstract variables implementation.
 *
 * Extend this as required, implementing "get", "set", and "clear".
 */
abstract class AbstractVariables implements VariablesInterface
{
    /**
     * Are we immutable?
     *
     * @var bool
     */
    private $immutable;

    /**
     * The record of loaded variables.
     *
     * @var \Dotenv\Environment\Adapter\ArrayAdapter
     */
    private $loaded;

    /**
     * Create a new environment variables instance.
     *
     * @param bool $immutable
     *
     * @return void
     */
    public function __construct($immutable)
    {
        $this->immutable = $immutable;
        $this->loaded = new ArrayAdapter();
    }

    /**
     * Get an environment variable.
     *
     * @param string $name
     *
     * @throws \InvalidArgumentException
     *
     * @return string|null
     */
    public function get($name)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException('Expected name to be a string.');
        }

        return $this->getInternal($name);
    }

    /**
     * Get an environment variable.
     *
     * @param string $name
     *
     * @return string|null
     */
    protected abstract function getInternal($name);

    /**
     * Set an environment variable.
     *
     * @param string      $name
     * @param string|null $value
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function set($name, $value = null)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException('Expected name to be a string.');
        }

        // Don't overwrite existing environment variables if we're immutable
        // Ruby's dotenv does this with `ENV[key] ||= value`.
        if ($this->isImmutable() && $this->get($name) !== null && $this->loaded->get($name)->isEmpty()) {
            return;
        }

        $this->setInternal($name, $value);
        $this->loaded->set($name, '');
    }

    /**
     * Set an environment variable.
     *
     * @param string      $name
     * @param string|null $value
     *
     * @return void
     */
    protected abstract function setInternal($name, $value = null);

    /**
     * Clear an environment variable.
     *
     * @param string $name
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function clear($name)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException('Expected name to be a string.');
        }

        // Don't clear anything if we're immutable.
        if ($this->isImmutable()) {
            return;
        }

        $this->clearInternal($name);
    }

    /**
     * Clear an environment variable.
     *
     * @param string $name
     *
     * @return void
     */
    protected abstract function clearInternal($name);

    /**
     * Determine if the environment is immutable.
     *
     * @return bool
     */
    public function isImmutable()
    {
        return $this->immutable;
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
        return is_string($name) && $this->get($name) !== null;
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
