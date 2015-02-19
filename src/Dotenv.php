<?php
/**
 * Dotenv
 *
 * Loads a `.env` file in the given directory and sets the environment vars
 */
class Dotenv
{
    /**
     * If true, then environment variables will not be overwritten
     * @var bool
     */
    protected static $immutable = true;

    /**
     * Load `.env` file in given directory
     */
    public static function load($path, $file = '.env')
    {
        if (!is_string($file)) {
            $file = '.env';
        }

        $filePath = rtrim($path, '/') . '/' . $file;
        if (!is_readable($filePath) || !is_file($filePath)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Dotenv: Environment file %s not found or not readable. " .
                    "Create file with your environment settings at %s",
                    $file,
                    $filePath
                )
            );
        }

        // Read file into an array of lines with auto-detected line endings
        $autodetect = ini_get('auto_detect_line_endings');
        ini_set('auto_detect_line_endings', '1');
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        ini_set('auto_detect_line_endings', $autodetect);

        foreach ($lines as $line) {
            // Disregard comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            // Only use non-empty lines that look like setters
            if (strpos($line, '=') !== false) {
                static::setEnvironmentVariable($line);
            }
        }
    }

    /**
     * Set a variable using:
     * - putenv
     * - $_ENV
     * - $_SERVER
     *
     * The environment variable value is stripped of single and double quotes.
     *
     * @param $name
     * @param null $value
     */
    public static function setEnvironmentVariable($name, $value = null)
    {
        list($name, $value) = static::normaliseEnvironmentVariable($name, $value);

        // Don't overwrite existing environment variables if we're immutable
        // Ruby's dotenv does this with `ENV[key] ||= value`.
        if (static::$immutable === true && !is_null(static::findEnvironmentVariable($name))) {
            return;
        }

        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    /**
     * Require specified ENV vars to be present, or throw Exception.
     * You can also pass through an set of allowed values for the environment variable.
     *
     * @throws \RuntimeException
     * @param  mixed             $environmentVariables the name of the environment variable or an array of names
     * @param  string[]          $allowedValues
     * @return true              (or throws exception on error)
     */
    public static function required($environmentVariables, array $allowedValues = array())
    {
        $environmentVariables = (array) $environmentVariables;
        $missingEnvironmentVariables = array();

        foreach ($environmentVariables as $environmentVariable) {
            $value = static::findEnvironmentVariable($environmentVariable);
            if (is_null($value)) {
                $missingEnvironmentVariables[] = $environmentVariable;
            } elseif ($allowedValues) {
                if (!in_array($value, $allowedValues)) {
                    // may differentiate in the future, but for now this does the job
                    $missingEnvironmentVariables[] = $environmentVariable;
                }
            }
        }

        if ($missingEnvironmentVariables) {
            throw new \RuntimeException(
                sprintf(
                    "Required environment variable missing, or value not allowed: '%s'",
                    implode("', '", $missingEnvironmentVariables)
                )
            );
        }

        return true;
    }

    /**
     * Takes value as passed in by developer and:
     * - ensures we're dealing with a separate name and value, breaking apart the name string if needed
     * - cleaning the value of quotes
     * - cleaning the name of quotes
     * - resolving nested variables
     *
     * @param $name
     * @param $value
     * @return array
     */
    protected static function normaliseEnvironmentVariable($name, $value)
    {
        list($name, $value) = static::splitCompoundStringIntoParts($name, $value);
        $name  = static::sanitiseVariableName($name);
        $value = static::sanitiseVariableValue($value);
        $value = static::resolveNestedVariables($value);

        return array($name, $value);
    }

    /**
     * If the $name contains an = sign, then we split it into 2 parts, a name & value
     *
     * @param $name
     * @param $value
     * @return array
     */
    protected static function splitCompoundStringIntoParts($name, $value)
    {
        if (strpos($name, '=') !== false) {
            list($name, $value) = array_map('trim', explode('=', $name, 2));
        }

        return array($name, $value);
    }

    /**
     * Strips quotes from the environment variable value.
     *
     * @param $value
     * @return string
     */
    protected static function sanitiseVariableValue($value)
    {
        $value = trim($value);
        if (!$value) return '';
        if (strpbrk($value[0], '"\'') !== false) { // value starts with a quote
            $quote = $value[0];
            $regexPattern = sprintf('/^
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
                /mx', $quote);
            $value = preg_replace($regexPattern, '$1', $value);
            $value = str_replace("\\$quote", $quote, $value);
            $value = str_replace('\\\\', '\\', $value);
        } else {
            $parts = explode(' #', $value, 2);
            $value = $parts[0];
        }
        return trim($value);
    }

    /**
     * Strips quotes and the optional leading "export " from the environment variable name.
     *
     * @param $name
     * @return string
     */
    protected static function sanitiseVariableName($name)
    {
        return trim(str_replace(array('export ', '\'', '"'), '', $name));
    }

    /**
     * Look for {$varname} patterns in the variable value and replace with an existing
     * environment variable.
     *
     * @param $value
     * @return mixed
     */
    protected static function resolveNestedVariables($value)
    {
        if (strpos($value, '$') !== false) {
            $value = preg_replace_callback(
                '/{\$([a-zA-Z0-9_]+)}/',
                function ($matchedPatterns) {
                    $nestedVariable = Dotenv::findEnvironmentVariable($matchedPatterns[1]);
                    if (is_null($nestedVariable)) {
                        return $matchedPatterns[0];
                    } else {
                        return  $nestedVariable;
                    }
                },
                $value
            );
        }

        return $value;
    }

    /**
     * Search the different places for environment variables and return first value found.
     * @param $name
     * @return string
     */
    public static function findEnvironmentVariable($name)
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
     * Check Dotenv immutable status. Returns true if immutable, false if mutable.
     * @return bool
     */
    public static function isImmutable()
    {
        return static::$immutable;
    }

    /**
     * Make Dotenv immutable. This means that once set, an environment variable cannot be overridden.
     */
    public static function makeImmutable()
    {
        static::$immutable = true;
    }

    /**
     * Make Dotenv mutable. Environment variables will act as, well, variables.
     */
    public static function makeMutable()
    {
        static::$immutable = false;
    }
}
