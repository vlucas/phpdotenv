<?php

namespace Dotenv\Environment;

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
     * Create a new environment variables instance.
     *
     * @param bool $immutable
     *
     * @return void
     */
    public function __construct($immutable)
    {
        $this->immutable = $immutable;
    }

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
        return !is_null($this->get($name));
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
