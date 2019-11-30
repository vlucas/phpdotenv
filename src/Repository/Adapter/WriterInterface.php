<?php

namespace Dotenv\Repository\Adapter;

interface WriterInterface extends AvailabilityInterface
{
    /**
     * Set an environment variable.
     *
     * @param string      $name
     * @param string|null $value
     *
     * @return void
     */
    public function set($name, $value = null);

    /**
     * Clear an environment variable.
     *
     * @param string $name
     *
     * @return void
     */
    public function clear($name);
}
