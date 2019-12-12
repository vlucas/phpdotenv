<?php

namespace Dotenv\Repository;

use ArrayAccess;

/**
 * @extends \ArrayAccess<string,string|null>
 */
interface RepositoryInterface extends ArrayAccess
{
    /**
     * Tells whether environment variable has been defined.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name);

    /**
     * Get an environment variable.
     *
     * @param string $name
     *
     * @throws \InvalidArgumentException
     *
     * @return string|null
     */
    public function get($name);

    /**
     * Set an environment variable.
     *
     * @param string      $name
     * @param string|null $value
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function set($name, $value = null);

    /**
     * Clear an environment variable.
     *
     * @param string $name
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function clear($name);
}
