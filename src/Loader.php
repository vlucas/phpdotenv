<?php

namespace Dotenv;

use Dotenv\Exception\InvalidFileException;
use Dotenv\Exception\InvalidPathException;

/**
 * This is the loaded class.
 *
 * It's responsible for loading variables by reading a file from disk and:
 * - stripping comments beginning with a `#`,
 * - parsing lines that look shell variable setters, e.g `export key = value`, `key="value"`.
 */
class Loader
{
    /**
     * The file path.
     *
     * @var string
     */
    protected $filePath;

    /**
     * Are we immutable?
     *
     * @var bool
     */
    protected $immutable;

    /**
     * Array of config variables
     *
     * @var array
     */
    protected $configFlags = [];

    /**
     * Have we set an environment variable yet?
     *
     * @var bool
     */
    protected $hasSetEnvironmentVariable = false;

    /**
     * Create a new loader instance.
     *
     * @param string $filePath
     * @param bool   $immutable
     *
     * @return void
     */
    public function __construct($filePath, $immutable = false)
    {
        $this->filePath = $filePath;
        $this->immutable = $immutable;
    }

    /**
     * Load `.env` file in given directory.
     *
     * @return array
     */
    public function load()
    {
        $this->ensureFileIsReadable();

        $filePath = $this->filePath;
        $lines = $this->readLinesFromFile($filePath);
        foreach ($lines as $line) {
            if (!$this->isComment($line) && $this->looksLikeConfigFlag($line)) {
                if ($this->hasSetEnvironmentVariable) {
                    throw new InvalidFileException('PHPDOTENV_ flags must appear before any environment variables are set');
                }
                $this->setConfigFlag($line);
            }
            if (!$this->isComment($line) && $this->looksLikeSetter($line)) {
                $this->setEnvironmentVariable($line);
            }
        }

        return $lines;
    }

    /**
     * Ensures the given filePath is readable.
     *
     * @throws \Dotenv\Exception\InvalidPathException
     *
     * @return void
     */
    protected function ensureFileIsReadable()
    {
        if (!is_readable($this->filePath) || !is_file($this->filePath)) {
            throw new InvalidPathException(sprintf('Unable to read the environment file at %s.', $this->filePath));
        }
    }

    /**
     * Normalise the given environment variable.
     *
     * Takes value as passed in by developer and:
     * - ensures we're dealing with a separate name and value, breaking apart the name string if needed,
     * - cleaning the value of quotes,
     * - cleaning the name of quotes,
     * - resolving nested variables.
     *
     * @param string $name
     * @param string $value
     *
     * @return array
     */
    protected function normaliseEnvironmentVariable($name, $value)
    {
        list($name, $value) = $this->splitCompoundStringIntoParts($name, $value);
        list($name, $value) = $this->sanitiseVariableName($name, $value);
        list($name, $value) = $this->sanitiseVariableValue($name, $value);

        $value = $this->resolveNestedVariables($value);

        return array($name, $value);
    }

    /**
     * Process the runtime filters.
     *
     * Called from the `VariableFactory`, passed as a callback in `$this->loadFromFile()`.
     *
     * @param string $name
     * @param string $value
     *
     * @return array
     */
    public function processFilters($name, $value)
    {
        list($name, $value) = $this->splitCompoundStringIntoParts($name, $value);
        list($name, $value) = $this->sanitiseVariableName($name, $value);
        list($name, $value) = $this->sanitiseVariableValue($name, $value);

        return array($name, $value);
    }

    /**
     * Read lines from the file, auto detecting line endings.
     *
     * @param string $filePath
     *
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
     * Determine if the line in the file is a comment, e.g. begins with a #.
     *
     * @param string $line
     *
     * @return bool
     */
    protected function isComment($line)
    {
        return strpos(ltrim($line), '#') === 0;
    }

    /**
     * Determine if the given line looks like it's setting a variable.
     *
     * @param string $line
     *
     * @return bool
     */
    protected function looksLikeSetter($line)
    {
        return strpos($line, '=') !== false;
    }

    /**
     * Determine if the given line looks like a config flag
     *
     * @param  string $line
     *
     * @return bool
     */
    protected function looksLikeConfigFlag($line)
    {
        return strpos(ltrim($line), 'PHPDOTENV_') === 0;
    }

    /**
     * Split the compound string into parts.
     *
     * If the `$name` contains an `=` sign, then we split it into 2 parts, a `name` & `value`
     * disregarding the `$value` passed in.
     *
     * @param string $name
     * @param string $value
     *
     * @return array
     */
    protected function splitCompoundStringIntoParts($name, $value)
    {
        if (strpos($name, '=') !== false) {
            list($name, $value) = array_map('trim', explode('=', $name, 2));
        }

        return array($name, $value);
    }

    /**
     * Strips quotes from the environment variable value.
     *
     * @param string $name
     * @param string $value
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return array
     */
    protected function sanitiseVariableValue($name, $value)
    {
        $value = trim($value);
        if (!$value) {
            return array($name, $value);
        }

        if ($this->beginsWithAQuote($value)) { // value starts with a quote
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
            $value = trim($parts[0]);

            // Unquoted values cannot contain whitespace
            if (preg_match('/\s+/', $value) > 0) {
                throw new InvalidFileException('Dotenv values containing spaces must be surrounded by quotes.');
            }
        }

        return array($name, trim($value));
    }

    /**
     * Resolve the nested variables.
     *
     * Look for {$varname} patterns in the variable value and replace with an
     * existing environment variable.
     *
     * @param string $value
     *
     * @return mixed
     */
    protected function resolveNestedVariables($value)
    {
        if (strpos($value, '$') !== false) {
            $loader = $this;
            $value = preg_replace_callback(
                '/\${([a-zA-Z0-9_]+)}/',
                function ($matchedPatterns) use ($loader) {
                    $nestedVariable = $loader->getEnvironmentVariable($matchedPatterns[1]);
                    if ($nestedVariable === null) {
                        return $matchedPatterns[0];
                    } else {
                        return $nestedVariable;
                    }
                },
                $value
            );
        }

        return $value;
    }

    /**
     * Strips quotes and the optional leading "export " from the environment variable name.
     *
     * @param string $name
     * @param string $value
     *
     * @return array
     */
    protected function sanitiseVariableName($name, $value)
    {
        $name = trim(str_replace(array('export ', '\'', '"'), '', $name));

        return array($name, $value);
    }

    /**
     * Removes EOL comments and santises config flag values
     *
     * @param  string $name
     * @return string
     */
    protected function sanitiseConfigFlag($name)
    {
        // Remove EOL comments from flags
        $parts = explode(' #', $name, 2);
        $name = trim($parts[0]);

        $name = trim(str_replace(array('export ', '\'', '"'), '', $name));

        return $name;
    }

    /**
     * Determine if the given string begins with a quote.
     *
     * @param string $value
     *
     * @return bool
     */
    protected function beginsWithAQuote($value)
    {
        return strpbrk($value[0], '"\'') !== false;
    }

    /**
     * Search the different places for environment variables and return first value found.
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getEnvironmentVariable($name)
    {
        switch (true) {
            case array_key_exists($name, $_ENV):
                return $_ENV[$name];
            case array_key_exists($name, $_SERVER):
                return $_SERVER[$name];
            default:
                $value = getenv($name);
                return $value === false ? null : $value; // switch getenv default to null
        }
    }

    /**
     * Set an environment variable.
     *
     * This is done using:
     * - putenv,
     * - $_ENV,
     * - $_SERVER.
     *
     * The environment variable value is stripped of single and double quotes.
     *
     * @param string      $name
     * @param string|null $value
     *
     * @return void
     */
    public function setEnvironmentVariable($name, $value = null)
    {
        if (!$this->hasSetEnvironmentVariable) {
            $this->hasSetEnvironmentVariable = true;
        }

        list($name, $value) = $this->normaliseEnvironmentVariable($name, $value);

        // Don't overwrite existing environment variables if we're immutable
        // Ruby's dotenv does this with `ENV[key] ||= value`.
        if ($this->immutable && $this->getEnvironmentVariable($name) !== null) {
            return;
        }

        // If PHP is running as an Apache module and an existing
        // Apache environment variable exists, overwrite it
        if (function_exists('apache_getenv') && function_exists('apache_setenv') && apache_getenv($name)) {
            apache_setenv($name, $value);
        }

        if (function_exists('putenv')) {
            putenv("$name=$value");
        }

        // Set the $_ENV superglobal if NO_SET_ENV flag has not been set
        if (!$this->hasConfigFlag('NO_SET_ENV')) {
            $_ENV[$name] = $value;
        }

        // Set the $_SERVER superglobal if NO_SET_SERVER flag has not been set
        if (!$this->hasConfigFlag('NO_SET_SERVER')) {
            $_SERVER[$name] = $value;
        }
    }

    /**
     * Clear an environment variable.
     *
     * This is not (currently) used by Dotenv but is provided as a utility
     * method for 3rd party code.
     *
     * This is done using:
     * - putenv,
     * - unset($_ENV, $_SERVER).
     *
     * @param string $name
     *
     * @see setEnvironmentVariable()
     *
     * @return void
     */
    public function clearEnvironmentVariable($name)
    {
        // Don't clear anything if we're immutable.
        if ($this->immutable) {
            return;
        }

        if (function_exists('putenv')) {
            putenv($name);
        }

        unset($_ENV[$name], $_SERVER[$name]);
    }

    /**
     * Set a config variable to the $config array
     *
     * @param string $name
     *
     * @return void
     */
    public function setConfigFlag($name)
    {
        $name = $this->sanitiseConfigFlag($name);

        // Remove the PHPDOTENV_ prefix
        $name = substr($name, 10);

        $this->configFlags[$name] = true;
    }

    /**
     * Utility method to check for config flag being set
     *
     * @param  string  $name
     *
     * @return boolean
     */
    public function hasConfigFlag($name)
    {
        return (isset($this->configFlags[$name]));
    }
}
