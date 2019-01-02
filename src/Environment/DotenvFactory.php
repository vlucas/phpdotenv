<?php

namespace Dotenv\Environment;

use Dotenv\Environment\Adapter\AdapterInterface;
use Dotenv\Environment\Adapter\ApacheAdapter;
use Dotenv\Environment\Adapter\EnvConstAdapter;
use Dotenv\Environment\Adapter\PutenvAdapter;
use Dotenv\Environment\Adapter\ServerConstAdapter;

/**
 * The default implementation of the environment factory interface.
 */
class DotenvFactory implements FactoryInterface
{
    /**
     * The set of adapters to use.
     *
     * @var \Dotenv\Environment\Adapter\AdapterInterface[]
     */
    protected $adapters;

    /**
     * Create a new dotenv environment factory instance.
     *
     * If no adapters are provided, then the defaults will be used.
     *
     * @param \Dotenv\Environment\Adapter\AdapterInterface[]|null $adapters
     *
     * @return void
     */
    public function __construct(array $adapters = null)
    {
        $this->adapters = array_filter($adapters ?: [new ApacheAdapter(), new EnvConstAdapter(), new ServerConstAdapter(), new PutenvAdapter()], function (AdapterInterface $adapter) {
            return $adapter->isSupported();
        });
    }

    /**
     * Creates a new mutable environment variables instance.
     *
     * @return \Dotenv\Environment\VariablesInterface
     */
    public function create()
    {
        return new DotenvVariables($this->adapters, false);
    }

    /**
     * Creates a new immutable environment variables instance.
     *
     * @return \Dotenv\Environment\VariablesInterface
     */
    public function createImmutable()
    {
        return new DotenvVariables($this->adapters, true);
    }
}
