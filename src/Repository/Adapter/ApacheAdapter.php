<?php

declare(strict_types=1);

namespace Dotenv\Repository\Adapter;

use PhpOption\None;
use PhpOption\Some;

final class ApacheAdapter implements AdapterInterface
{
    /**
     * Create a new apache adapter instance.
     *
     * @return void
     */
    private function __construct()
    {
        //
    }

    /**
     * Create a new instance of the adapter, if it is available.
     *
     * @return \PhpOption\Option<\Dotenv\Repository\Adapter\AdapterInterface>
     */
    public static function create()
    {
        if (self::isSupported()) {
            /** @var \PhpOption\Option<AdapterInterface> */
            return Some::create(new self());
        }

        return None::create();
    }

    /**
     * Determines if the adapter is supported.
     *
     * This happens if PHP is running as an Apache module.
     *
     * @return bool
     */
    private static function isSupported()
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
    public function get(string $name)
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
     * @return bool
     */
    public function set(string $name, string $value = null)
    {
        if (apache_getenv($name) !== false) {
            apache_setenv($name, (string) $value);
        }

        return true;
    }

    /**
     * Clear an environment variable.
     *
     * @param string $name
     *
     * @return bool
     */
    public function clear(string $name)
    {
        // Nothing to do here.

        return true;
    }
}
