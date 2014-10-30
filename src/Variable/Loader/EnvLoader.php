<?php

namespace Dotenv\Variable\Loader;

use Dotenv\Variable\LoadsVariables;

/**
 * Loads Variables by reading a file from disk and:
 * - stripping comments beginning with a `#`
 * - parsing lines that look shell variable setters, e.g `export key = value`, `key="value"` …etc.
 */
class EnvLoader extends AbstractLoader implements LoadsVariables
{
    public function __construct()
    {
        $this->addFilter(array($this, 'splitCompoundStringIntoParts'));
        $this->addFilter(array($this, 'sanitiseVariableName'));
        $this->addFilter(array($this, 'sanitiseVariableValue'));
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    public function extension()
    {
        return '.env';
    }

    /**
     * {@inheritDoc}
     */
    public function loadFromFile($directory, $file, $immutable = false)
    {
        $filePath = $this->getFilePath($directory, $file);
        $lines = $this->readLinesFromFile($filePath);
        foreach ($lines as $line) {
            if ($this->isComment($line)) {
                continue;
            }
            if ($this->looksLikeSetter($line)) {
                $variable = $this->parse($line, null); // first filter will split line
                $variable->commit($immutable);
            }
        }
    }

    /**
     * @param string $filePath
     * @return array
     */
    protected function readLinesFromFile($filePath)
    {
        // Read file into an array of lines with auto-detected line endings
        $autodetect = ini_get('auto_detect_line_endings');
        ini_set('auto_detect_line_endings', '1');
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        ini_set('auto_detect_line_endings', $autodetect);
        return $lines;
    }


    /**
     * @param $line
     * @return bool
     */
    protected function isComment($line)
    {
        return strpos(trim($line), '#') === 0;
    }

    /**
     * @param $line
     * @return bool
     */
    protected function looksLikeSetter($line)
    {
        return strpos($line, '=') !== false;
    }

    /*
     * If the $name contains an = sign, then we split it into 2 parts, a name & value
     */
    protected function splitCompoundStringIntoParts($name, $value)
    {
        if (strpos($name, '=') !== false) {
            list($name, $value) = array_map('trim', explode('=', $name, 2));
        }
        return array($name, $value);
    }

    /*
     * Strips quotes from the environment variable value.
     */
    protected function sanitiseVariableValue($name, $value)
    {
        $value = trim($value);
        if (!$value) {
            return array($name, $value);
        }

        if (strpbrk($value[0], '"\'') !== false) { // value starts with a quote
            $quote = $value[0];
            $regexPattern = sprintf(
                '/^
                %1$s          # match a quote at the start of the value
                (             # capturing sub-pattern used
                 (?:          # we do not need to capture this
                  [^%1$s\\\\] # any character other than a quote or backslash
                  |\\\\\\\\   # or two backslashes together
                  |\\\\%1$s   # or an escaped quote e.g \"
                 )*           # as many characters that match the previous rules
                )             # end of the capturing sub-pattern
                %1$s          # and the closing quote
                .*$           # and discard any string after the closing quote
                /mx',
                $quote
            );
            $value = preg_replace($regexPattern, '$1', $value);
            $value = str_replace("\\$quote", $quote, $value);
            $value = str_replace('\\\\', '\\', $value);
        } else {
            $parts = explode(' #', $value, 2);
            $value = $parts[0];
        }
        return array($name, trim($value));
    }

    /*
     * Strips quotes and the optional leading "export " from the environment variable name.
     */
    protected function sanitiseVariableName($name, $value)
    {
        $name = trim(str_replace(array('export ', '\'', '"'), '', $name));
        return array($name, $value);
    }
}
