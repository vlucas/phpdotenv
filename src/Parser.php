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
                case self::INITIAL_STATE:
                    if ($char === '"') {
                        return array($data[0], self::QUOTED_STATE);
                    } else {
                        return array($data[0].$char, self::UNQUOTED_STATE);
                    }
                case self::UNQUOTED_STATE:
                    if ($char === '#') {
                        return array($data[0], self::COMMENT_STATE);
                    } elseif (ctype_space($char)) {
                        return array($data[0], self::WHITESPACE_STATE);
                    } else {
                        return array($data[0].$char, self::UNQUOTED_STATE);
                    }
                case self::QUOTED_STATE:
                    if ($char === '"') {
                        return array($data[0], self::WHITESPACE_STATE);
                    } elseif ($char === '\\') {
                        return array($data[0], self::ESCAPE_STATE);
                    } else {
                        return array($data[0].$char, self::QUOTED_STATE);
                    }
                case self::ESCAPE_STATE:
                    if ($char === '"' || $char === '\\') {
                        return array($data[0].$char, self::QUOTED_STATE);
                    } else {
                        return array($data[0].'\\'.$char, self::QUOTED_STATE);
                    }
                case self::WHITESPACE_STATE:
                    if ($char === '#') {
                        return array($data[0], self::COMMENT_STATE);
                    } elseif (!ctype_space($char)) {
                        if ($data[0] !== '' && $data[0][0] === '#') {
                            return array('', self::COMMENT_STATE);
                        } else {
                            throw new InvalidFileException('Dotenv values containing spaces must be surrounded by quotes.');
                        }
                    } else {
                        return array($data[0], self::WHITESPACE_STATE);
                    }
                case self::COMMENT_STATE:
                    return array($data[0], self::COMMENT_STATE);
            }
        }, array('', self::INITIAL_STATE));

        return trim($data[0]);
    }
}
