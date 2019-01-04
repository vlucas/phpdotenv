<?php

namespace Dotenv\Regex;

use PhpOption\Option;

class Regex
{
    /**
     * Perform a preg replace, failing with an exception.
     *
     * @param string $pattern
     * @param string $repalcement
     * @param string $subject
     *
     * @return \Dotenv\Regex\Result
     */
    public static function pregReplace($pattern, $replacement, $subject)
    {
        $result = (string) @preg_replace($pattern, $replacement, $subject);

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
        return Option::fromValue(get_defined_constants(true))
            ->filter(function (array $consts) {
                return isset($consts['pcre']) && defined('ARRAY_FILTER_USE_KEY');
            })
            ->map(function (array $consts) {
                return array_filter($consts['pcre'], function ($msg) {
                    return substr($msg, -6) === '_ERROR';
                }, ARRAY_FILTER_USE_KEY);
            })
            ->flatMap(function (array $errors) use ($code) {
                return Option::fromValue(
                    array_search($code, $errors, true)
                );
            })
            ->getOrElse('PREG_ERROR');
    }
}
