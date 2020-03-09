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
     * Read an environment variable, if it exists.
     *
     * @param string $name
     *
     * @return \PhpOption\Option<string|null>
     */
    public function read(string $name)
    {
        $value = apache_getenv($name);

        // apache adapter does not support empty/null values
        if ($value !== '' && $value !== null) {
            return ValueLifter::lift($value);
        }

        return None::create();
    }

    /**
     * Write to an environment variable, if possible.
     *
     * @param string      $name
     * @param string|null $value
     *
     * @return bool
     */
    public function write(string $name, string $value = null)
    {
        return apache_setenv($name, (string) $value);
    }

    /**
     * Delete an environment variable, if possible.
     *
     * @param string $name
     *
     * @return bool
     */
    public function delete(string $name)
    {
        return apache_setenv($name, '');
    }
}
