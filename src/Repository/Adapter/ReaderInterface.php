<?php

declare(strict_types=1);

namespace Dotenv\Repository\Adapter;

interface ReaderInterface
{
    /**
     * Get an environment variable, if it exists.
     *
     * @param string $name
     *
     * @return \PhpOption\Option<string|null>
     */
    public function get(string $name);
}
