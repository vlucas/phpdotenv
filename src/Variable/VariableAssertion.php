<?php

namespace Dotenv\Variable;

/**
 * Assertions to be applied against a number of Variables.
 */
class VariableAssertion
{
    /**
     * @var Variable[]
     */
    protected $variables;

    public function __construct(array $variables)
    {
        $this->variables = $variables;
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
     * @param string[] $choices
     *
     * @return VariableAssertion
     */
    public function inArray(array $choices)
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
        foreach ($this->variables as $variable) {
            $value = $variable->get();
            if (call_user_func($callback, $value) === false) {
                $variablesFailingAssertion[] = $variable->name()." $message";
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
