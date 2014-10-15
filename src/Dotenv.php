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
    private static $immutable = true;

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
                    "Dotenv: Environment file .env not found or not readable. " .
                    "Create file with your environment settings at %s",
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
            if (strpos($line, '#') !== false) {
                $line = substr($line, 0, strpos($line, '#'));
            }
            // Only use non-empty lines that look like setters
            if (strpos($line, '=') !== false) {
                self::setEnvironmentVariable($line);
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
        list($name, $value) = self::normaliseEnvironmentVariable($name, $value);

        // Don't overwrite existing environment variables if we're immutable
        // Ruby's dotenv does this with `ENV[key] ||= value`.
        if (self::$immutable === true && !is_null(self::findEnvironmentVariable($name))) {
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
            $value = self::findEnvironmentVariable($environmentVariable);
            // check allowed values first, we may allow eg. "0"
            if ($allowedValues) {
                if (!in_array($value, $allowedValues)) {
                    // may differentiate in the future, but for now this does the job
                    $missingEnvironmentVariables[] = $environmentVariable;
                }
            } elseif (empty($value)) {
                $missingEnvironmentVariables[] = $environmentVariable;
            }
        }

        if ($missingEnvironmentVariables) {
            throw new \RuntimeException(
                sprintf(
                    "Required environment variable missing, empty, or value not allowed: '%s'",
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
    private static function normaliseEnvironmentVariable($name, $value)
    {
        list($name, $value) = self::splitCompoundStringIntoParts($name, $value);
        $name  = self::sanitiseVariableName($name);
        $value = self::sanitiseVariableValue($value);
        $value = self::resolveNestedVariables($value);

        return array($name, $value);
    }

    /**
     * If the $name contains an = sign, then we split it into 2 parts, a name & value
     *
     * @param $name
     * @param $value
     * @return array
     */
    private static function splitCompoundStringIntoParts($name, $value)
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
    private static function sanitiseVariableValue($value)
    {
        return trim(str_replace(array('\'', '"'), '', $value));
    }

    /**
     * Strips quotes and the optional leading "export " from the environment variable name.
     *
     * @param $name
     * @return string
     */
    private static function sanitiseVariableName($name)
    {
        return trim(str_replace(array('export ', '\'', '"'), '', $name));
    }

    /**
     * Look for $varname patterns in the variable value and replace with an existing
     * environment variable.
     *
     * @param $value
     * @return mixed
     */
    private static function resolveNestedVariables($value)
    {
        if (strpos($value, '$') !== false) {
            $value = preg_replace_callback(
                '/\${?([a-zA-Z0-9_]+)}?/',
                function ($matchedPatterns) {
                    return  Dotenv::findEnvironmentVariable($matchedPatterns[1]);
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
     * Make Dotenv immutable. This means that once set, an environment variable cannot be overridden.
     */
    public static function makeImmutable()
    {
        self::$immutable = true;
    }

    /**
     * Make Dotenv mutable. Environment variables will act as, well, variables.
     */
    public static function makeMutable()
    {
        self::$immutable = false;
    }
}
