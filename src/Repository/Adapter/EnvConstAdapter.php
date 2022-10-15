<?php

namespace Dotenv\Repository\Adapter;

use PhpOption\None;
use PhpOption\Some;

class EnvConstAdapter implements AvailabilityInterface, ReaderInterface, WriterInterface
{
    /**
     * Determines if the adapter is supported.
     *
     * @return bool
     */
    public function isSupported()
    {
        return true;
    }

    /**
     * Get an environment variable, if it exists.
     *
     * @param non-empty-string $name
     *
     * @return \PhpOption\Option<string|null>
     */
    public function get($name)
    {
        if (!array_key_exists($name, $_ENV)) {
            return None::create();
        }

        $value = $_ENV[$name];

        if (is_scalar($value)) {
            /** @var \PhpOption\Option<string|null> */
            return Some::create((string) $value);
        }

        if (null === $value) {
            /** @var \PhpOption\Option<string|null> */
            return Some::create(null);
        }

        return None::create();
    }

    /**
     * Set an environment variable.
     *
     * @param non-empty-string $name
     * @param string|null      $value
     *
     * @return void
     */
    public function set($name, $value = null)
    {
        $_ENV[$name] = $value;
    }

    /**
     * Clear an environment variable.
     *
     * @param non-empty-string $name
     *
     * @return void
     */
    public function clear($name)
    {
        unset($_ENV[$name]);
    }
}
