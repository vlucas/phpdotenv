<?php

namespace Dotenv\Environment;

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
     * @return string|null
     */
    protected function getInternal($name)
    {
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
     * @return void
     */
    protected function setInternal($name, $value = null)
    {
        foreach ($this->adapters as $adapter) {
            $adapter->set($name, $value);
        }
    }

    /**
     * Clear an environment variable.
     *
     * @param string $name
     *
     * @return void
     */
    protected function clearInternal($name)
    {
        foreach ($this->adapters as $adapter) {
            $adapter->clear($name);
        }
    }
}
