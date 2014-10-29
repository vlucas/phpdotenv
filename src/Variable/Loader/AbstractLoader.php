<?php namespace Dotenv\Variable\Loader;

use Dotenv\Variable\LoadsVariables;
use Dotenv\Variable\Variable;

abstract class AbstractLoader implements LoadsVariables
{
    /** @var Callable[] filters to run over variable values */
    protected $filters = array();

    public function __construct()
    {
        $this->addFilter(array($this, 'resolveNestedVariables'));
    }

    /**
     * @param callable $filter
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
     * Takes a variable name and value, applies all the registered filters, and returns a ner Variable.
     *
     * @param string $name name of name=value pair
     * @param string $value
     * @return Variable
     */
    protected function parse($name, $value = null)
    {
        foreach ($this->filters as $filter) {
            list($name, $value) = call_user_func($filter, $name, $value);
        }
        $variable = new Variable($name);
        $variable->prepareValue($value);
        return $variable;
    }

    /*
     * Look for {$varname} patterns in the variable value and replace with an existing
     * environment variable.
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

    /**
     * @param string $path
     * @param string $file
     * @return string path to the file
     */
    protected function getFilePath($path, $file)
    {
        if (!is_string($file)) {
            $file = $this->extension();
        }

        $filePath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;
        $this->ensureFileIsReadable($filePath);
        return $filePath;
    }

    /**
     * @param $filePath
     */
    private function ensureFileIsReadable($filePath)
    {
        if (!is_readable($filePath) || !is_file($filePath)) {
            throw new \InvalidArgumentException(sprintf(
                "Dotenv: Environment file .env not found or not readable. " .
                "Create file with your environment settings at %s",
                $filePath
            ));
        }
    }
}
