<?php

declare(strict_types=1);

namespace Dotenv\Repository\Adapter;

use PhpOption\None;
use PhpOption\Option;
use PhpOption\Some;

final class PutenvAdapter implements AdapterInterface
{
    /**
     * Create a new putenv adapter instance.
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
     * @return bool
     */
    private static function isSupported()
    {
        return function_exists('getenv') && function_exists('putenv');
    }

    /**
     * Get an environment variable, if it exists.
     *
     * @param string $name
     *
     * @return \PhpOption\Option<string|null>
     */
    public function get(string $name)
    {
        /** @var \PhpOption\Option<string|null> */
        return Option::fromValue(getenv($name), false);
    }

    /**
     * Set an environment variable.
     *
     * @param string      $name
     * @param string|null $value
     *
     * @return bool
     */
    public function set(string $name, string $value = null)
    {
        putenv("$name=$value");

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
        putenv($name);

        return true;
    }
}
