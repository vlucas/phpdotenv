<?php

namespace Dotenv;

use Dotenv\Exception\InvalidFileException;

class Parser
{
    const INITIAL_STATE = 0;
    const QUOTED_STATE = 1;
    const ESCAPE_STATE = 2;
    const WHITESPACE_STATE = 3;
    const COMMENT_STATE = 4;

    /**
     * Parse the given variable name.
     *
     * @param string $name
     *
     * @return string
     */
    public static function parseName($name)
    {
        return trim(str_replace(array('export ', '\'', '"'), '', $name));
    }

    /**
     * Parse the given variable value.
     *
     * @param string $value
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return string
     */
    public static function parseValue($value)
    {
        if ($value === '') {
            return '';
        } elseif ($value[0] === '"' || $value[0] === '\'') {
            return Parser::parseQuotedValue($value);
        } else {
            return Parser::parseUnquotedValue($value);
        }
    }

    /**
     * Parse the given quoted value.
     *
     * @param string $value
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return string
     */
    public static function parseQuotedValue($value)
    {
        $result = array_reduce(str_split($value), function ($data, $char) use ($value) {
            switch ($data[1]) {
                case Parser::INITIAL_STATE:
                    if ($char === '"' || $char === '\'') {
                        return array($data[0], Parser::QUOTED_STATE);
                    } else {
                        throw new InvalidFileException(
                            'Expected the value to start with a quote.'
                        );
                    }
                case Parser::QUOTED_STATE:
                    if ($char === $value[0]) {
                        return array($data[0], Parser::WHITESPACE_STATE);
                    } elseif ($char === '\\') {
                        return array($data[0], Parser::ESCAPE_STATE);
                    } else {
                        return array($data[0].$char, Parser::QUOTED_STATE);
                    }
                case Parser::ESCAPE_STATE:
                    if ($char === $value[0] || $char === '\\') {
                        return array($data[0].$char, Parser::QUOTED_STATE);
                    } else {
                        return array($data[0].'\\'.$char, Parser::QUOTED_STATE);
                    }
                case Parser::WHITESPACE_STATE:
                    if ($char === '#') {
                        return array($data[0], Parser::COMMENT_STATE);
                    } elseif (!ctype_space($char)) {
                        throw new InvalidFileException(
                            'Dotenv values containing spaces must be surrounded by quotes.'
                        );
                    } else {
                        return array($data[0], Parser::WHITESPACE_STATE);
                    }
                case Parser::COMMENT_STATE:
                    return array($data[0], Parser::COMMENT_STATE);
            }
        }, array('', Parser::INITIAL_STATE));

        if ($result[1] === Parser::QUOTED_STATE || $result[1] === Parser::ESCAPE_STATE) {
            throw new InvalidFileException(
                'Dotenv values starting with a quote must finish with a closing quote.'
            );
        }

        return trim($result[0]);
    }

    /**
     * Parse the given unquoted value.
     *
     * @param string $value
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return string
     */
    public static function parseUnquotedValue($value)
    {
        $parts = explode(' #', $value, 2);
        $value = trim($parts[0]);

        // Unquoted values cannot contain whitespace
        if (preg_match('/\s+/', $value) > 0) {
            // Check if value is a comment (usually triggered when empty value with comment)
            if (preg_match('/^#/', $value) > 0) {
                $value = '';
            } else {
                throw new InvalidFileException('Dotenv values containing spaces must be surrounded by quotes.');
            }
        }

        return trim($value);
    }
}
