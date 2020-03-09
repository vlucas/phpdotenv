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
     * Read an environment variable, if it exists.
     *
     * @param string $name
     *
     * @return \PhpOption\Option<string|null>
     */
    public function read(string $name)
    {
        if (array_key_exists($name, $this->variables)) {
            return Some::create($this->variables[$name]);
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
        $this->variables[$name] = $value;

        return true;
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
        unset($this->variables[$name]);

        return true;
    }
}
