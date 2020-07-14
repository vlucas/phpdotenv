<?php

namespace Dotenv\Regex;

use Dotenv\Result\Error;
use Dotenv\Result\Result;
use Dotenv\Result\Success;

class Regex
{
    /**
     * Perform a preg match, wrapping up the result.
     *
     * @param string $pattern
     * @param string $subject
     *
     * @return \Dotenv\Result\Result<int,string>
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
     * @param string   $pattern
     * @param string   $replacement
     * @param string   $subject
     * @param int|null $limit
     *
     * @return \Dotenv\Result\Result<string,string>
     */
    public static function replace($pattern, $replacement, $subject, $limit = null)
    {
        return self::pregAndWrap(function ($subject) use ($pattern, $replacement, $limit) {
            return (string) @preg_replace($pattern, $replacement, $subject, $limit === null ? -1 : $limit);
        }, $subject);
    }

    /**
     * Perform a preg replace callback, wrapping up the result.
     *
     * @param string   $pattern
     * @param callable $callback
     * @param string   $subject
     * @param int|null $limit
     *
     * @return \Dotenv\Result\Result<string,string>
     */
    public static function replaceCallback($pattern, callable $callback, $subject, $limit = null)
    {
        return self::pregAndWrap(function ($subject) use ($pattern, $callback, $limit) {
            return (string) @preg_replace_callback($pattern, $callback, $subject, $limit === null ? -1 : $limit);
        }, $subject);
    }

    /**
     * Perform a preg split, wrapping up the result.
     *
     * @param string $pattern
     * @param string $subject
     *
     * @return \Dotenv\Result\Result<string[],string>
     */
    public static function split($pattern, $subject)
    {
        return self::pregAndWrap(function ($subject) use ($pattern) {
            /** @var string[] */
            return (array) @preg_split($pattern, $subject);
        }, $subject);
    }

    /**
     * Perform a preg operation, wrapping up the result.
     *
     * @template V
     *
     * @param callable(string):V $operation
     * @param string             $subject
     *
     * @return \Dotenv\Result\Result<V,string>
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
