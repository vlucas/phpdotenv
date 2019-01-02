<?php

namespace Dotenv\Environment;

/**
 * This environment factory interface.
 *
 * If you need custom implementations of the variables interface, implement
 * this interface, and use your implementation in the loader.
 */
interface FactoryInterface
{
    /**
     * Creates a new mutable environment variables instance.
     *
     * @return \Dotenv\Environment\VariablesInterface
     */
    public function create();

    /**
     * Creates a new immutable environment variables instance.
     *
     * @return \Dotenv\Environment\VariablesInterface
     */
    public function createImmutable();
}
