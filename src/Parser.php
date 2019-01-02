<?php

namespace Dotenv;

use Dotenv\Exception\InvalidFileException;

class Parser
{
    /**
     * Parse the given environment variable entry into a name and value.
     *
     * Takes value as passed in by developer and:
     * - breaks up the line into a name and value,
     * - cleaning the value of quotes,
     * - cleaning the name of quotes.
     *
     * @param string $entry
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return array
     */
    public static function parse($entry)
    {
        list($name, $value) = self::splitStringIntoParts($entry);

        return [self::sanitiseName($name), self::sanitiseValue($value)];
    }

    /**
     * Split the compound string into parts.
     *
     * If the `$line` contains an `=` sign, then we split it into 2 parts.
     *
     * @param string $line
     *
     * @return array
     */
    private static function splitStringIntoParts($line)
    {
        $name = $line;
        $value = null;

        if (strpos($line, '=') !== false) {
            list($name, $value) = array_map('trim', explode('=', $line, 2));
        }

        return [$name, $value];
    }

    /**
     * Strips quotes and the optional leading "export " from the variable name.
     *
     * @param string $name
     *
     * @return string
     */
    private static function sanitiseName($name)
    {
        return trim(str_replace(['export ', '\'', '"'], '', $name));
    }

    /**
     * Strips quotes from the environment variable value.
     *
     * @param string|null $value
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return string|null
     */
    private static function sanitiseValue($value)
    {
        if ($value === null || trim($value) === '') {
            return $value;
        }

        if (self::beginsWithAQuote($value)) { // value starts with a quote
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
                    throw new InvalidFileException(
                        'Dotenv values containing spaces must be surrounded by quotes.'
                    );
                }
            }
        }

        return $value;
    }

    /**
     * Determine if the given string begins with a quote.
     *
     * @param string $value
     *
     * @return bool
     */
    private static function beginsWithAQuote($value)
    {
        return isset($value[0]) && ($value[0] === '"' || $value[0] === '\'');
    }
}
