<?php

namespace Dotenv\Repository\Adapter;

use PhpOption\None;

class ApacheAdapter implements AvailabilityInterface, ReaderInterface, WriterInterface
{
    /**
     * Determines if the adapter is supported.
     *
     * This happens if PHP is running as an Apache module.
     *
     * @return bool
     */
    public function isSupported()
    {
        return function_exists('apache_getenv') && function_exists('apache_setenv');
    }

    /**
     * Get an environment variable, if it exists.
     *
     * This is intentionally not implemented, since this adapter exists only as
     * a means to overwrite existing apache environment variables.
     *
     * @param string $name
     *
     * @return \PhpOption\Option<string|null>
     */
    public function get($name)
    {
        return None::create();
    }

    /**
     * Set an environment variable.
     *
     * Only if an existing apache variable exists do we overwrite it.
     *
     * @param string      $name
     * @param string|null $value
     *
     * @return void
     */
    public function set($name, $value = null)
    {
        if (apache_getenv($name) !== false) {
            apache_setenv($name, (string) $value);
        }
    }

    /**
     * Clear an environment variable.
     *
     * @param string $name
     *
     * @return void
     */
    public function clear($name)
    {
        // Nothing to do here.
    }
}
