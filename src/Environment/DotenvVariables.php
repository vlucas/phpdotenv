<?php

namespace Dotenv\Environment;

use InvalidArgumentException;

/**
 * The default implementation of the environment variables interface.
 */
class DotenvVariables extends AbstractVariables
{
    /**
     * The set of adapters to use.
     *
     * @var \Dotenv\Environment\Adapter\AdapterInterface[]
     */
    protected $adapters;

    /**
     * Create a new dotenv environment variables instance.
     *
     * @param \Dotenv\Environment\Adapter\AdapterInterface[] $adapters
     * @param bool                                           $immutable
     *
     * @return void
     */
    public function __construct(array $adapters, $immutable)
    {
        $this->adapters = $adapters;
        parent::__construct($immutable);
    }

    /**
     * Get an environment variable.
     *
     * We do this by querying our adapters sequentially.
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

        foreach ($this->adapters as $adapter) {
            $result = $adapter->get($name);
            if ($result->isDefined()) {
                return $result->get();
            }
        }
    }

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
        if ($this->isImmutable() && $this->get($name) !== null) {
            return;
        }

        foreach ($this->adapters as $adapter) {
            $adapter->set($name, $value);
        }
    }

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

        foreach ($this->adapters as $adapter) {
            $adapter->clear($name);
        }
    }
}
