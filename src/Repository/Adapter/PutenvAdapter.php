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
     * @return \PhpOption\Option<self>
     */
    public static function create()
    {
        if (self::isSupported()) {
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
        return \function_exists('getenv') && \function_exists('putenv');
    }

    /**
     * Read an environment variable, if it exists.
     *
     * @param non-empty-string $name
     *
     * @return \PhpOption\Option<string>
     */
    public function read(string $name)
    {
        if (\strpos($name, "\0") !== false) {
            return None::create();
        }

        /** @var \PhpOption\Option<string> */
        return Option::fromValue(\getenv($name), false)->filter(static function ($value) {
            return \is_string($value);
        });
    }

    /**
     * Write to an environment variable, if possible.
     *
     * @param non-empty-string $name
     * @param string           $value
     *
     * @return bool
     */
    public function write(string $name, string $value)
    {
        if (\strpos($name, "\0") !== false || \strpos($value, "\0") !== false) {
            return false;
        }

        \putenv("$name=$value");

        return true;
    }

    /**
     * Delete an environment variable, if possible.
     *
     * @param non-empty-string $name
     *
     * @return bool
     */
    public function delete(string $name)
    {
        if (\strpos($name, "\0") !== false) {
            return false;
        }

        \putenv($name);

        return true;
    }
}
