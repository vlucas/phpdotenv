<?php

namespace Dotenv\Variable;

/**
 * Classes implementing this interface are capable of reading environment variables into an
 * `array` (`hash`) from a file.
 */
interface LoadsVariables
{
    /**
     * The extension this loader uses.
     *
     * @return string
     */
    public function extension();

    /**
     * Load the variables from a file.
     *
     * @param \Dotenv\Variable\VariableFactory $variableFactory factory to use to make variables
     * @param string                           $filePath        the path to configuration file
     * @param bool                             $immutable       should existing variables be treated as immutable
     *
     * @return string[string]
     */
    public function loadFromFile(VariableFactory $variableFactory, $filePath, $immutable = false);
}
