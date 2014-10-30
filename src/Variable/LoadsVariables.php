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
     * @param string $directory path to the directory holding
     * @param string $file the name of the file to use that sits within $directory
     * @param bool $immutable whether the existing variables should be treated as immutable
     * @return array[string]string `['key' => 'value']`
     */
    public function loadFromFile($directory, $file, $immutable = false);
}
