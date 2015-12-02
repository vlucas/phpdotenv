<?php

namespace Dotenv;

/**
 * Represents system environment and its actions
 *
 */
class Environment
{
    /**
     * Are we immutable?
     *
     * @var bool
     */
    protected $immutable = true;

    /**
     * @var Variable[]
     */
    private $variables = array();

    /**
     * @param boolean $immutable
     */
    public function setImmutable($immutable)
    {
        $this->immutable = $immutable;
    }

    /**
     * Set an environment variable.
     *
     * This is done using:
     * - putenv
     * - $_ENV
     * - $_SERVER.
     *
     * The environment variable value is stripped of single and double quotes.
     *
     * @param Variable $variable
     */
    public function setVariable(Variable $variable)
    {
        $variable->resolveNested($this);
        $this->variables[$variable->getName()] = $variable;

        // Don't overwrite existing environment variables if we're immutable
        if (true === $this->immutable && !is_null($this->getVariable($variable->getName())->getValue())) {
            return;
        }

        putenv($variable->getName() . "=" . $variable->getValue());
        $_ENV[$variable->getName()] = $variable->getValue();
        $_SERVER[$variable->getName()] = $variable->getValue();
    }

    /**
     * Clear an environment variable.
     *
     * This is not (currently) used by Dotenv but is provided as a utility
     * method for 3rd party code.
     *
     * This is done using:
     * - putenv
     * - unset($_ENV, $_SERVER)
     *
     * @param Variable $variable
     *
     */
    public function clearVariable(Variable $variable)
    {
        // Don't clear anything if we're immutable.
        if ( ! $this->immutable) {
            unset($this->variables[$variable->getName()]);
            putenv($variable->getName());
            unset($_ENV[$variable->getName()]);
            unset($_SERVER[$variable->getName()]);
        }
    }

    /**
     * Search the different places for environment variables and return first value found.
     *
     * @param string $name
     *
     * @return Variable
     */
    public function getVariable($name)
    {
        switch (true) {
            case array_key_exists($name, $_ENV):
                return Variable::make($name, $_ENV[$name]);

            case array_key_exists($name, $_SERVER):
                return Variable::make($name, $_SERVER[$name]);

            default:
                $value = getenv($name);
                return (
                    false === $value ?
                    Variable::make($name) :
                    Variable::make($name, $value)
                ); // switch getenv default to null
        }
    }
}
