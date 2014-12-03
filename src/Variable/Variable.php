<?php

namespace Dotenv\Variable;

/**
 * Used to get or set an environment variable.
 *
 * Supports setting a pending value, followed by a commit to simplify handling of mutability.
 */
class Variable
{
    /**
     * The variable name.
     *
     * @var string
     */
    protected $name;

    /**
     * Create a variable instance.
     *
     * @param string $name
     */
    protected $pendingValue;

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Prepare a value that can then be committed depending on immutability requirements.
     *
     * @param string $value
     *
     * @return void
     */
    public function prepareValue($value)
    {
        $this->pendingValue = $value;
    }

    /**
     * Commit the pending variable value.
     *
     * @param bool $immutable
     *
     * @return bool
     */
    public function commit($immutable = false)
    {
        if (!$this->okToWriteVariable($immutable)) {
            $this->pendingValue = null;

            return false;
        }

        $name = $this->name;
        $value = $this->pendingValue;

        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;

        return true;
    }

    /**
     * Get the variable.
     *
     * @return string|null
     */
    public function get()
    {
        $name = $this->name;
        if (array_key_exists($name, $_ENV)) {
            return $_ENV[$name];
        } elseif (array_key_exists($name, $_SERVER)) {
            return $_SERVER[$name];
        } else {
            return getenv($name) === false ? null : getenv($name);
        }
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * A guard that returns true if it's ok to continue to write this variable.
     *
     * @param bool $immutable
     *
     * @return bool
     */
    protected function okToWriteVariable($immutable)
    {
        return ($immutable === false || $this->get() === null);
    }
}
