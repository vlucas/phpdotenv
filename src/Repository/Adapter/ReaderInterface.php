<?php

namespace Dotenv\Repository\Adapter;

interface ReaderInterface extends AvailabilityInterface
{
    /**
     * Get an environment variable, if it exists.
     *
     * @param string $name
     *
     * @return \PhpOption\Option<string>
     */
    public function get($name);
}
