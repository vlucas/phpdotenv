<?php

declare(strict_types=1);

namespace Dotenv\Util;

use GrahamCampbell\ResultType\Error;
use GrahamCampbell\ResultType\Success;
use PhpOption\Option;

/**
 * @internal
 */
final class Str
{
    /**
     * This class is a singleton.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    private function __construct()
    {
        //
    }

    /**
     * Convert a string to UTF-8 from the given encoding.
     *
     * @param string      $input
     * @param string|null $encoding
     *
     * @return \GrahamCampbell\ResultType\Result<string, string>
     */
    public static function utf8(string $input, ?string $encoding = null)
    {
        if ($encoding !== null && !self::isValidEncoding($encoding)) {
            return Error::create(
                \sprintf('Illegal character encoding [%s] specified.', $encoding)
            );
        }

        $converted = $encoding === null ?
            @\mb_convert_encoding($input, 'UTF-8') :
            @\mb_convert_encoding($input, 'UTF-8', $encoding);

        if (!is_string($converted)) {
            return Error::create(
                \sprintf('Conversion from encoding [%s] failed.', $encoding ?? 'NULL')
            );
        }

        /**
         * this is for support UTF-8 with BOM encoding
         * @see https://en.wikipedia.org/wiki/Byte_order_mark
         * @see https://github.com/vlucas/phpdotenv/issues/500
         */
        if (\substr($converted, 0, 3) === "\xEF\xBB\xBF") {
            $converted = \substr($converted, 3);
        }

        return Success::create($converted);
    }

    /**
     * Is the given character encoding valid?
     *
     * @param string $encoding
     *
     * @return bool
     */
    private static function isValidEncoding(string $encoding)
    {
        foreach (\mb_list_encodings() as $candidate) {
            if (\strcasecmp($candidate, $encoding) === 0) {
                return true;
            }

            foreach (@\mb_encoding_aliases($candidate) as $alias) {
                if (\strcasecmp($alias, $encoding) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Search for a given substring of the input.
     *
     * @param string $haystack
     * @param string $needle
     *
     * @return \PhpOption\Option<int<0, max>>
     */
    public static function pos(string $haystack, string $needle)
    {
        /** @var \PhpOption\Option<int<0, max>> */
        return Option::fromValue(\mb_strpos($haystack, $needle, 0, 'UTF-8'), false);
    }

    /**
     * Grab the specified substring of the input.
     *
     * @param string   $input
     * @param int      $start
     * @param int|null $length
     *
     * @return string
     */
    public static function substr(string $input, int $start, ?int $length = null)
    {
        return \mb_substr($input, $start, $length, 'UTF-8');
    }

    /**
     * Compute the length of the given string.
     *
     * @param string $input
     *
     * @return int
     */
    public static function len(string $input)
    {
        return \mb_strlen($input, 'UTF-8');
    }
}
