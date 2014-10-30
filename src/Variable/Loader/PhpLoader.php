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
            $variable = $variableFactory->create($name, $value);
            $variable->commit($immutable);
        }
    }
}
