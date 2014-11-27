<?php

namespace Dotenv\Variable\Loader;

use Dotenv\Variable\LoadsVariables;
use Dotenv\Variable\VariableFactory;

/**
 * Loads Variables by including a file and using it's returned array as the variable hash.
 */
class PhpLoader implements LoadsVariables
{
    /**
     * {@inheritDoc}
     */
    public function extension()
    {
        return '.env.php';
    }

    /**
     * {@inheritDoc}
     */
    public function loadFromFile(VariableFactory $variableFactory, $filePath, $immutable = false)
    {
        $variables = require $filePath;
        if (!is_array($variables)) {
            return; // nothing to process
        }

        foreach ($variables as $name => $value) {
            $value = $this->castValueToString($value);
            $variable = $variableFactory->create($name, $value);
            $variable->commit($immutable);
        }
    }

    /**
     * Casts scalars to a string representation. All other types return empty string.
     * 
     * @param mixed $value
     *
     * @return string
     */
    private function castValueToString($value)
    {
        if (is_bool($value)) {
            return $value === true ? 'true' : 'false';
        } elseif (is_scalar($value)) {
            return (string) $value;
        } else {
            return '';
        }
    }
}
