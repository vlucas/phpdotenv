<?php

namespace Dotenv\Loader;

use Dotenv\Exception\InvalidFileException;
use Dotenv\Result\Error;
use Dotenv\Result\Success;

class Parser
{
    const INITIAL_STATE = 0;
    const UNQUOTED_STATE = 1;
    const SINGLE_QUOTED_STATE = 2;
    const DOUBLE_QUOTED_STATE = 3;
    const ESCAPE_SEQUENCE_STATE = 4;
    const WHITESPACE_STATE = 5;
    const COMMENT_STATE = 6;

    /**
     * Parse the given environment variable entry into a name and value.
     *
     * @param string $entry
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return array{string,\Dotenv\Loader\Value|null}
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
     * @return array{string,string|null}
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
        return preg_match('~\A[a-zA-Z0-9_.]+\z~', $name) === 1;
    }

    /**
     * Strips quotes and comments from the environment variable value.
     *
     * @param string|null $value
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return \Dotenv\Loader\Value|null
     */
    private static function parseValue($value)
    {
        if ($value === null) {
            return null;
        }

        if (trim($value) === '') {
            return Value::blank();
        }

        return array_reduce(str_split($value), function ($data, $char) use ($value) {
            return self::processChar($data[1], $char)->mapError(function ($err) use ($value) {
                throw new InvalidFileException(
                    self::getErrorMessage($err, $value)
                );
            })->mapSuccess(function ($val) use ($data) {
                return [$data[0]->append($val[0], $val[1]), $val[2]];
            })->getSuccess();
        }, [Value::blank(), self::INITIAL_STATE])[0];
    }

    /**
     * Process the given character.
     *
     * @param int    $state
     * @param string $char
     *
     * @return \Dotenv\Result\Result<array{string,bool,int},string>
     */
    private static function processChar($state, $char)
    {
        switch ($state) {
            case self::INITIAL_STATE:
                if ($char === '\'') {
                    return Success::create(['', false, self::SINGLE_QUOTED_STATE]);
                } elseif ($char === '"') {
                    return Success::create(['', false, self::DOUBLE_QUOTED_STATE]);
                } elseif ($char === '#') {
                    return Success::create(['', false, self::COMMENT_STATE]);
                } elseif ($char === '$') {
                    return Success::create([$char, true, self::UNQUOTED_STATE]);
                } else {
                    return Success::create([$char, false, self::UNQUOTED_STATE]);
                }
            case self::UNQUOTED_STATE:
                if ($char === '#') {
                    return Success::create(['', false, self::COMMENT_STATE]);
                } elseif (ctype_space($char)) {
                    return Success::create(['', false, self::WHITESPACE_STATE]);
                } elseif ($char === '$') {
                    return Success::create([$char, true, self::UNQUOTED_STATE]);
                } else {
                    return Success::create([$char, false, self::UNQUOTED_STATE]);
                }
            case self::SINGLE_QUOTED_STATE:
                if ($char === '\'') {
                    return Success::create(['', false, self::WHITESPACE_STATE]);
                } else {
                    return Success::create([$char, false, self::SINGLE_QUOTED_STATE]);
                }
            case self::DOUBLE_QUOTED_STATE:
                if ($char === '"') {
                    return Success::create(['', false, self::WHITESPACE_STATE]);
                } elseif ($char === '\\') {
                    return Success::create(['', false, self::ESCAPE_SEQUENCE_STATE]);
                } elseif ($char === '$') {
                    return Success::create([$char, true, self::DOUBLE_QUOTED_STATE]);
                } else {
                    return Success::create([$char, false, self::DOUBLE_QUOTED_STATE]);
                }
            case self::ESCAPE_SEQUENCE_STATE:
                if ($char === '"' || $char === '\\') {
                    return Success::create([$char, false, self::DOUBLE_QUOTED_STATE]);
                } elseif ($char === '$') {
                    return Success::create([$char, false, self::DOUBLE_QUOTED_STATE]);
                } elseif (in_array($char, ['f', 'n', 'r', 't', 'v'], true)) {
                    return Success::create([stripcslashes('\\'.$char), false, self::DOUBLE_QUOTED_STATE]);
                } else {
                    return Error::create('an unexpected escape sequence');
                }
            case self::WHITESPACE_STATE:
                if ($char === '#') {
                    return Success::create(['', false, self::COMMENT_STATE]);
                } elseif (!ctype_space($char)) {
                    return Error::create('unexpected whitespace');
                } else {
                    return Success::create(['', false, self::WHITESPACE_STATE]);
                }
            case self::COMMENT_STATE:
                return Success::create(['', false, self::COMMENT_STATE]);
        }
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
