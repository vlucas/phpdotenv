<?php

namespace Dotenv\Variable\Loader;

use Dotenv\Variable\LoadsVariables;

/**
 * Loads Variables by including a file and using it's returned array as the variable hash.
 */
class PhpLoader extends AbstractLoader implements LoadsVariables
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
    public function loadFromFile($directory, $file, $immutable = false)
    {
        $filePath = $this->getFilePath($directory, $file);
        $variables = require $filePath;
        if (!is_array($variables)) {
            return; // nothing to process
        }

        foreach ($variables as $name => $value) {
            $variable = $this->parse($name, $value);
            $variable->commit($immutable);
        }
    }
}
