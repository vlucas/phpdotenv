<?php

declare(strict_types=1);

namespace Dotenv\Repository\Adapter;

interface WriterInterface
{
    /**
     * Set an environment variable.
     *
     * @param string      $name
     * @param string|null $value
     *
     * @return bool
     */
    public function set(string $name, string $value = null);

    /**
     * Clear an environment variable.
     *
     * @param string $name
     *
     * @return bool
     */
    public function clear(string $name);
}
