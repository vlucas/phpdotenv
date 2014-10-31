<?php

namespace Dotenv\Variable;

/**
 * Classes implementing this interface are capable of reading environment variables into an
 * `array` (`hash`) from a file.
 */
interface LoadsVariables
{
    /**
     * @return string the extension this loader uses.
     */
    public function extension();

    /**
     * @param  VariableFactory $variableFactory factory to use to make variables
     * @param  string $filePath the path to configuration file
     * @param  bool $immutable whether the existing variables should be treated as immutable
     * @return array[string]string `['key' => 'value']`
     */
    public function loadFromFile(VariableFactory $variableFactory, $filePath, $immutable = false);
}
