<?php

namespace Dotenv\Regex;

class Regex
{
    /**
     * Perform a preg match, wrapping up the result.
     *
     * @param string $pattern
     * @param string $subject
     *
     * @return \Dotenv\Regex\Result
     */
    public static function match($pattern, $subject)
    {
        return self::pregAndWrap(function ($subject) use ($pattern) {
            return (int) @preg_match($pattern, $subject);
        }, $subject);
    }

    /**
     * Perform a preg replace, wrapping up the result.
     *
     * @param string $pattern
     * @param string $replacement
     * @param string $subject
     *
     * @return \Dotenv\Regex\Result
     */
    public static function replace($pattern, $replacement, $subject)
    {
        return self::pregAndWrap(function ($subject) use ($pattern, $replacement) {
            return (string) @preg_replace($pattern, $replacement, $subject);
        }, $subject);
    }

    /**
     * Perform a preg replace callback, wrapping up the result.
     *
     * @param string   $pattern
     * @param callable $callback
     * @param string   $subject
     *
     * @return \Dotenv\Regex\Result
     */
    public static function replaceCallback($pattern, callable $callback, $subject)
    {
        return self::pregAndWrap(function ($subject) use ($pattern, $callback) {
            return (string) @preg_replace_callback($pattern, $callback, $subject);
        }, $subject);
    }

    /**
     * Perform a preg operation, wrapping up the result.
     *
     * @param callable $operation
     * @param string   $subject
     *
     * @return \Dotenv\Regex\Result
     */
    private static function pregAndWrap(callable $operation, $subject)
    {
        $result = $operation($subject);

        if (($e = preg_last_error()) !== PREG_NO_ERROR) {
            return Error::create(self::lookupError($e));
        }

        return Success::create($result);
    }

    /**
     * Lookup the preg error code.
     *
     * @param int $code
     *
     * @return string
     */
    private static function lookupError($code)
    {
        if (defined('PREG_JIT_STACKLIMIT_ERROR') && $code === PREG_JIT_STACKLIMIT_ERROR) {
            return 'JIT stack limit exhausted';
        }

        switch ($code) {
            case PREG_INTERNAL_ERROR:
                return 'Internal error';
            case PREG_BAD_UTF8_ERROR:
                return 'Malformed UTF-8 characters, possibly incorrectly encoded';
            case PREG_BAD_UTF8_OFFSET_ERROR:
                return 'The offset did not correspond to the beginning of a valid UTF-8 code point';
            case PREG_BACKTRACK_LIMIT_ERROR:
                return 'Backtrack limit exhausted';
            case PREG_RECURSION_LIMIT_ERROR:
                return 'Recursion limit exhausted';
            default:
                return 'Unknown error';
        }
    }
}
