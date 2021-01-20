<?php

namespace Dotenv;

use Dotenv\Exception\InvalidFileException;
use Dotenv\Regex\Regex;

class Parser
{
    const INITIAL_STATE = 0;
    const UNQUOTED_STATE = 1;
    const QUOTED_STATE = 2;
    const ESCAPE_STATE = 3;
    const WHITESPACE_STATE = 4;
    const COMMENT_STATE = 5;

    /**
     * Parse the given environment variable entry into a name and value.
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

        return [self::parseName($name), self::parseValue($value)];
    }

    /**
     * Split the compound string into parts.
     *
     * @param string $line
     *
     * @throws \Dotenv\Exception\InvalidFileException
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

        if ($name === '') {
            throw new InvalidFileException(
                self::getErrorMessage('an unexpected equals', $line)
            );
        }

        return [$name, $value];
    }

    /**
     * Strips quotes and the optional leading "export " from the variable name.
     *
     * @param string $name
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return string
     */
    private static function parseName($name)
    {
        $name = trim(str_replace(['export ', '\'', '"'], '', $name));

        if (!self::isValidName($name)) {
            throw new InvalidFileException(
                self::getErrorMessage('an invalid name', $name)
            );
        }

        return $name;
    }

    /**
     * Is the given variable name valid?
     *
     * @param string $name
     *
     * @return bool
     */
    private static function isValidName($name)
    {
        return Regex::match('~\A[a-zA-Z0-9_.]+\z~', $name)->success()->getOrElse(0) === 1;
    }

    /**
     * Strips quotes and comments from the environment variable value.
     *
     * @param string|null $value
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return string|null
     */
    private static function parseValue($value)
    {
        if ($value === null || trim($value) === '') {
            return $value;
        }

        $result = array_reduce(str_split($value), function ($data, $char) use ($value) {
            switch ($data[1]) {
                case self::INITIAL_STATE:
                    if ($char === '"' || $char === '\'') {
                        return [$data[0], self::QUOTED_STATE];
                    } elseif ($char === '#') {
                        return [$data[0], self::COMMENT_STATE];
                    } else {
                        return [$data[0].$char, self::UNQUOTED_STATE];
                    }
                case self::UNQUOTED_STATE:
                    if ($char === '#') {
                        return [$data[0], self::COMMENT_STATE];
                    } elseif (ctype_space($char)) {
                        return [$data[0], self::WHITESPACE_STATE];
                    } else {
                        return [$data[0].$char, self::UNQUOTED_STATE];
                    }
                case self::QUOTED_STATE:
                    if ($char === $value[0]) {
                        return [$data[0], self::WHITESPACE_STATE];
                    } elseif ($char === '\\') {
                        return [$data[0], self::ESCAPE_STATE];
                    } else {
                        return [$data[0].$char, self::QUOTED_STATE];
                    }
                case self::ESCAPE_STATE:
                    if ($char === $value[0] || $char === '\\') {
                        return [$data[0].$char, self::QUOTED_STATE];
                    } elseif (in_array($char, ['f', 'n', 'r', 't', 'v'], true)) {
                        return [$data[0].stripcslashes('\\'.$char), self::QUOTED_STATE];
                    } else {
                        throw new InvalidFileException(
                            self::getErrorMessage('an unexpected escape sequence', $value)
                        );
                    }
                case self::WHITESPACE_STATE:
                    if ($char === '#') {
                        return [$data[0], self::COMMENT_STATE];
                    } elseif (!ctype_space($char)) {
                        throw new InvalidFileException(
                            self::getErrorMessage('unexpected whitespace', $value)
                        );
                    } else {
                        return [$data[0], self::WHITESPACE_STATE];
                    }
                case self::COMMENT_STATE:
                    return [$data[0], self::COMMENT_STATE];
            }
        }, ['', self::INITIAL_STATE]);

        if ($result[1] === self::QUOTED_STATE || $result[1] === self::ESCAPE_STATE) {
            throw new InvalidFileException(
                self::getErrorMessage('a missing closing quote', $value)
            );
        }

        return $result[0];
    }

    /**
     * Generate a friendly error message.
     *
     * @param string $cause
     * @param string $subject
     *
     * @return string
     */
    private static function getErrorMessage($cause, $subject)
    {
        return sprintf(
            'Failed to parse dotenv file due to %s. Failed at [%s].',
            $cause,
            strtok($subject, "\n")
        );
    }
}
