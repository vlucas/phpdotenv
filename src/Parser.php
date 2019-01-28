<?php

namespace Dotenv;

use Dotenv\Exception\InvalidFileException;

class Parser
{
    const INITIAL_STATE = 0;
    const UNQUOTED_STATE = 1;
    const QUOTED_STATE = 2;
    const ESCAPE_STATE = 3;
    const WHITESPACE_STATE = 4;
    const COMMENT_STATE = 5;

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
        $data = array_reduce(str_split($value), function ($data, $char) use ($value) {
            switch ($data[1]) {
                case Parser::INITIAL_STATE:
                    if ($char === '"') {
                        return array($data[0], Parser::QUOTED_STATE);
                    } else {
                        return array($data[0].$char, Parser::UNQUOTED_STATE);
                    }
                case Parser::UNQUOTED_STATE:
                    if ($char === '#') {
                        return array($data[0], Parser::COMMENT_STATE);
                    } elseif (ctype_space($char)) {
                        return array($data[0], Parser::WHITESPACE_STATE);
                    } else {
                        return array($data[0].$char, Parser::UNQUOTED_STATE);
                    }
                case Parser::QUOTED_STATE:
                    if ($char === '"') {
                        return array($data[0], Parser::WHITESPACE_STATE);
                    } elseif ($char === '\\') {
                        return array($data[0], Parser::ESCAPE_STATE);
                    } else {
                        return array($data[0].$char, Parser::QUOTED_STATE);
                    }
                case Parser::ESCAPE_STATE:
                    if ($char === '"' || $char === '\\') {
                        return array($data[0].$char, Parser::QUOTED_STATE);
                    } else {
                        return array($data[0].'\\'.$char, Parser::QUOTED_STATE);
                    }
                case Parser::WHITESPACE_STATE:
                    if ($char === '#') {
                        return array($data[0], Parser::COMMENT_STATE);
                    } elseif (!ctype_space($char)) {
                        if ($data[0] !== '' && $data[0][0] === '#') {
                            return array('', Parser::COMMENT_STATE);
                        } else {
                            throw new InvalidFileException('Dotenv values containing spaces must be surrounded by quotes.');
                        }
                    } else {
                        return array($data[0], Parser::WHITESPACE_STATE);
                    }
                case Parser::COMMENT_STATE:
                    return array($data[0], Parser::COMMENT_STATE);
            }
        }, array('', Parser::INITIAL_STATE));

        return trim($data[0]);
    }
}
