<?php

namespace Dotenv;

use Dotenv\Environment\FactoryInterface;
use Dotenv\Exception\InvalidFileException;
use Dotenv\Exception\InvalidPathException;

/**
 * This is the loaded class.
 *
 * It's responsible for loading variables by reading a file from disk and:
 * - stripping comments beginning with a `#`,
 * - parsing lines that look shell variable setters, e.g `export key = value`, `key="value"`.
 * - multiline variable look always start with a " and end with it, e.g: `key="value
 *                                                                             value"`
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
     * The environment factory instance.
     *
     * @var \Dotenv\Environment\FactoryInterface
     */
    protected $envFactory;

    /**
     * The environment variables instance.
     *
     * @var \Dotenv\Environment\VariablesInterface
     */
    protected $envVariables;

    /**
     * The list of environment variables declared inside the 'env' file.
     *
     * @var string[]
     */
    protected $variableNames = [];

    /**
     * Create a new loader instance.
     *
     * @param string                               $filePath
     * @param \Dotenv\Environment\FactoryInterface $envFactory
     * @param bool                                 $immutable
     *
     * @return void
     */
    public function __construct($filePath, FactoryInterface $envFactory, $immutable = false)
    {
        $this->filePath = $filePath;
        $this->envFactory = $envFactory;
        $this->setImmutable($immutable);
    }

    /**
     * Set immutable value.
     *
     * @param bool $immutable
     *
     * @return $this
     */
    public function setImmutable($immutable = false)
    {
        $this->envVariables = $immutable
            ? $this->envFactory->createImmutable()
            : $this->envFactory->create();

        return $this;
    }

    /**
     * Load `.env` file in given directory.
     *
     * @throws \Dotenv\Exception\InvalidPathException|\Dotenv\Exception\InvalidFileException
     *
     * @return array
     */
    public function load()
    {
        $this->ensureFileIsReadable();

        $filePath = $this->filePath;
        $lines = $this->readLinesFromFile($filePath);

        // multiline
        $multiline = false;
        $multilineBuffer = [];

        foreach ($lines as $line) {
            list($multiline, $line, $multilineBuffer) = $this->multilineProcess($multiline, $line, $multilineBuffer);

            if (!$multiline && !$this->isComment($line) && $this->looksLikeSetter($line)) {
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
     * @param string      $name
     * @param string|null $value
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return array
     */
    protected function normaliseEnvironmentVariable($name, $value)
    {
        list($name, $value) = $this->processFilters($name, $value);

        $value = $this->resolveNestedVariables($value);

        return [$name, $value];
    }

    /**
     * Process the runtime filters.
     *
     * Called from `normaliseEnvironmentVariable` and the `VariableFactory`, passed as a callback in `$this->loadFromFile()`.
     *
     * @param string      $name
     * @param string|null $value
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return array
     */
    public function processFilters($name, $value)
    {
        list($name, $value) = $this->splitCompoundStringIntoParts($name, $value);
        list($name, $value) = $this->sanitiseVariableName($name, $value);
        list($name, $value) = $this->sanitiseVariableValue($name, $value);

        return [$name, $value];
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
     * Used to make all multiline variable process.
     *
     * @param bool   $multiline
     * @param string $line
     * @param array  $buffer
     *
     * @return array
     */
    protected function multilineProcess($multiline, $line, $buffer)
    {
        // check if $line can be multiline variable
        if ($this->looksLikeMultilineStart($line)) {
            $multiline = true;
        }
        if ($multiline) {
            array_push($buffer, $line);

            if ($this->looksLikeMultilineStop($line)) {
                $multiline = false;
                $line = implode("\n", $buffer);
                $buffer = [];
            }
        }

        return [$multiline, $line, $buffer];
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
        $line = ltrim($line);

        return isset($line[0]) && $line[0] === '#';
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
     * Determine if the given line can be the start of a multiline variable.
     *
     * @param string $line
     *
     * @return bool
     */
    protected function looksLikeMultilineStart($line)
    {
        return strpos($line, '="') !== false && substr_count($line, '"') === 1;
    }

    /**
     * Determine if the given line can be the start of a multiline variable.
     *
     * @param string $line
     *
     * @return bool
     */
    protected function looksLikeMultilineStop($line)
    {
        return strpos($line, '"') !== false && substr_count($line, '="') === 0;
    }

    /**
     * Split the compound string into parts.
     *
     * If the `$name` contains an `=` sign, then we split it into 2 parts, a `name` & `value`
     * disregarding the `$value` passed in.
     *
     * @param string      $name
     * @param string|null $value
     *
     * @return array
     */
    protected function splitCompoundStringIntoParts($name, $value)
    {
        if (strpos($name, '=') !== false) {
            list($name, $value) = array_map('trim', explode('=', $name, 2));
        }

        return [$name, $value];
    }

    /**
     * Strips quotes from the environment variable value.
     *
     * @param string      $name
     * @param string|null $value
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return array
     */
    protected function sanitiseVariableValue($name, $value)
    {
        if ($value === null || trim($value) === '') {
            return [$name, $value];
        }

        if ($this->beginsWithAQuote($value)) { // value starts with a quote
            $quote = $value[0];
            $regexPattern = sprintf(
                '/^
                %1$s           # match a quote at the start of the value
                (              # capturing sub-pattern used
                 (?:           # we do not need to capture this
                  [^%1$s\\\\]+ # any character other than a quote or backslash
                  |\\\\\\\\    # or two backslashes together
                  |\\\\%1$s    # or an escaped quote e.g \"
                 )*            # as many characters that match the previous rules
                )              # end of the capturing sub-pattern
                %1$s           # and the closing quote
                .*$            # and discard any string after the closing quote
                /mx',
                $quote
            );
            $value = preg_replace($regexPattern, '$1', $value);
            $value = str_replace("\\$quote", $quote, $value);
            $value = str_replace('\\\\', '\\', $value);
        } else {
            $parts = explode(' #', $value, 2);
            $value = $parts[0];

            // Unquoted values cannot contain whitespace
            if (preg_match('/\s+/', $value) > 0) {
                // Check if value is a comment (usually triggered when empty value with comment)
                if (preg_match('/^#/', $value) > 0) {
                    $value = '';
                } else {
                    throw new InvalidFileException('Dotenv values containing spaces must be surrounded by quotes.');
                }
            }
        }

        return [$name, $value];
    }

    /**
     * Resolve the nested variables.
     *
     * Look for ${varname} patterns in the variable value and replace with an
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
                '/\${([a-zA-Z0-9_.]+)}/',
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
        $name = trim(str_replace(['export ', '\'', '"'], '', $name));

        return [$name, $value];
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
        return isset($value[0]) && ($value[0] === '"' || $value[0] === '\'');
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
        return $this->envVariables->get($name);
    }

    /**
     * Set an environment variable.
     *
     * The environment variable value is stripped of single and double quotes.
     *
     * @param string      $name
     * @param string|null $value
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return void
     */
    public function setEnvironmentVariable($name, $value = null)
    {
        list($name, $value) = $this->normaliseEnvironmentVariable($name, $value);

        $this->variableNames[] = $name;

        $this->envVariables->set($name, $value);
    }

    /**
     * Clear an environment variable.
     *
     * This method only expects names in normal form.
     *
     * @param string $name
     *
     * @return void
     */
    public function clearEnvironmentVariable($name)
    {
        $this->envVariables->clear($name);
    }

    /**
     * Get the list of environment variables names.
     *
     * @return string[]
     */
    public function getEnvironmentVariableNames()
    {
        return $this->variableNames;
    }
}
