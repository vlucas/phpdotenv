<?php

namespace Dotenv\Variable;

/**
 * Used to create a variable from a name and value.
 *
 * Filters can be applied to the name and value before creation.
 */
class VariableFactory
{
    /**
     * The filters to run over variable values.
     *
     * @var Callable[]
     */
    protected $filters = array();

    public function __construct()
    {
        $this->addFilter(array($this, 'resolveNestedVariables'));
    }

    /**
     * Add a filter to be called before the variable is created.
     *
     * @param callable $filter
     *
     * @return $this
     */
    public function addFilter($filter)
    {
        if (!is_callable($filter)) {
            throw new \InvalidArgumentException('Filter no callable');
        }
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * Takes a variable name & value and applies all the registered filters.
     *
     * @param string   $name
     * @param string   $value
     * @param callable $runtimeFilter to be applied before existing filters.
     *
     * @return \Dotenv\Variable\Variable
     */
    public function create($name, $value, $runtimeFilter = null)
    {
        $filters = $this->filters;

        if (is_callable($runtimeFilter)) {
            array_unshift($filters, $runtimeFilter);
        }

        foreach ($filters as $filter) {
            list($name, $value) = call_user_func($filter, $name, $value);
        }

        $variable = new Variable($name);
        $variable->prepareValue($value);

        return $variable;
    }

    /**
     * Look for `{$varname}` patterns in the variable value and replace with an existing
     * environment variable.
     *
     * @param string $name
     * @param string $value
     *
     * @return array
     */
    protected function resolveNestedVariables($name, $value)
    {
        if (strpos($value, '$') !== false) {
            $value = preg_replace_callback(
                '/{\$([a-zA-Z0-9_]+)}/',
                function ($matchedPatterns) {
                    $nestedVariable = dotenv()->get($matchedPatterns[1]);
                    if (is_null($nestedVariable)) {
                        return $matchedPatterns[0];
                    } else {
                        return  $nestedVariable;
                    }
                },
                $value
            );
        }

        return array($name, $value);
    }
}
