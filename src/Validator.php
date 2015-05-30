<?php

namespace Dotenv;

/**
 * Validations to be applied against a number of Variables.
 */
class Validator
{
    /**
     * @param array $variables
     */
    protected $variables;
    protected $loader;

    public function __construct(array $variables, Loader $loader)
    {
        $this->variables = $variables;
        $this->loader = $loader;

        $this->assertCallback(
            function ($value) {
                return !is_null($value);
            },
            'is missing'
        );
    }

    /**
     * Assert that each variable is not empty.
     *
     * @return VariableAssertion
     */
    public function notEmpty()
    {
        return $this->assertCallback(
            function ($value) {
                return (strlen(trim($value)) > 0);
            },
            'is empty'
        );
    }

    /**
     * Assert that each variable is amongst the given choices.
     *
     * @param  string[]          $choices
     * @return VariableAssertion
     */
    public function allowedValues(array $choices)
    {
        return $this->assertCallback(
            function ($value) use ($choices) {
                return in_array($value, $choices);
            },
            'is not an allowed value'
        );
    }

    /**
     * Assert that the callback returns true for each variable.
     *
     * @param callable $callback
     * @param string   $message  to use if the assertion fails
     *
     * @return $this
     */
    protected function assertCallback($callback, $message = 'failed callback assertion')
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Callback must be callable');
        }

        $variablesFailingAssertion = array();
        foreach ($this->variables as $variableName) {
            $variableValue = $this->loader->getEnvironmentVariable($variableName);
            if (call_user_func($callback, $variableValue) === false) {
                $variablesFailingAssertion[] = $variableName." $message";
            }
        }

        if (count($variablesFailingAssertion) > 0) {
            throw new \RuntimeException(sprintf(
                'One or more environment variables failed assertions: %s',
                implode(', ', $variablesFailingAssertion)
            ));
        }

        return $this;
    }
}
