<?php

declare(strict_types=1);

namespace Dotenv\Repository\Adapter;

interface WriterInterface
{
    /**
     * Write to an environment variable, if possible.
     *
     * @param string      $name
     * @param string|null $value
     *
     * @return bool
     */
    public function write(string $name, string $value = null);

    /**
     * Delete an environment variable, if possible.
     *
     * @param string $name
     *
     * @return bool
     */
    public function delete(string $name);
}
