<?php

namespace Dotenv\Variable\Loader;

use Dotenv\Variable\VariableFactory;

/**
 * Loads Variables from a JSON file.
 */
class JsonLoader extends PhpLoader
{
    /**
     * {@inheritDoc}
     */
    public function extension()
    {
        return '.env.json';
    }

    /**
     * {@inheritDoc}
     */
    public function loadFromFile(VariableFactory $variableFactory, $filePath, $immutable = false)
    {
        $content = file_get_contents($filePath);
        $variables = @json_decode($content, true);
        if (!is_array($variables)) {
            return; // nothing to process
        }

        foreach ($variables as $name => $value) {
            $value = $this->castValueToString($value);
            $variable = $variableFactory->create($name, $value);
            $variable->commit($immutable);
        }
    }
}
