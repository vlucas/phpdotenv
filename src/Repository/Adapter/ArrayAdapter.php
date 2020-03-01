<?php

declare(strict_types=1);

namespace Dotenv\Repository\Adapter;

use PhpOption\None;
use PhpOption\Some;

final class ArrayAdapter implements AdapterInterface
{
    /**
     * The variables and their values.
     *
     * @var array<string,string|null>
     */
    private $variables;

    /**
     * Create a new array adapter instance.
     *
     * @return void
     */
    private function __construct()
    {
        $this->variables = [];
    }

    /**
     * Create a new instance of the adapter, if it is available.
     *
     * @return \PhpOption\Option<\Dotenv\Repository\Adapter\AdapterInterface>
     */
    public static function create()
    {
        /** @var \PhpOption\Option<AdapterInterface> */
        return Some::create(new self());
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
        if (array_key_exists($name, $this->variables)) {
            return Some::create($this->variables[$name]);
        }

        return None::create();
    }

    /**
     * Set an environment variable.
     *
     * @param string      $name
     * @param string|null $value
     *
     * @return void
     */
    public function set(string $name, string $value = null)
    {
        $this->variables[$name] = $value;
    }

    /**
     * Clear an environment variable.
     *
     * @param string $name
     *
     * @return void
     */
    public function clear(string $name)
    {
        unset($this->variables[$name]);
    }
}
