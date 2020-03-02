<?php

declare(strict_types=1);

namespace Dotenv\Regex;

use Dotenv\Result\Error;
use Dotenv\Result\Success;

final class Regex
{
    /**
     * Perform a preg match, wrapping up the result.
     *
     * @param string $pattern
     * @param string $subject
     *
     * @return \Dotenv\Result\Result<int,string>
     */
    public static function match(string $pattern, string $subject)
    {
        return self::pregAndWrap(function (string $subject) use ($pattern) {
            return (int) @preg_match($pattern, $subject);
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
    public static function replaceCallback(string $pattern, callable $callback, string $subject, int $limit = null)
    {
        return self::pregAndWrap(function (string $subject) use ($pattern, $callback, $limit) {
            return (string) @preg_replace_callback($pattern, $callback, $subject, $limit ?? -1);
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
    public static function split(string $pattern, string $subject)
    {
        return self::pregAndWrap(function (string $subject) use ($pattern) {
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
    private static function pregAndWrap(callable $operation, string $subject)
    {
        $result = $operation($subject);

        if (preg_last_error() !== PREG_NO_ERROR) {
            return Error::create(preg_last_error_msg());
        }

        return Success::create($result);
    }
}
