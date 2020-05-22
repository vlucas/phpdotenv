<?php

declare(strict_types=1);

namespace Dotenv\Parser;

use Dotenv\Util\Str;

final class Lines
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
     * Process the array of lines of environment variables.
     *
     * This will produce an array of raw entries, one per variable.
     *
     * @param string[] $lines
     *
     * @return string[]
     */
    public static function process(array $lines)
    {
        $output = [];
        $multiline = false;
        $multilineBuffer = [];

        foreach ($lines as $line) {
            [$multiline, $line, $multilineBuffer] = self::multilineProcess($multiline, $line, $multilineBuffer);

            if (!$multiline && !self::isCommentOrWhitespace($line)) {
                $output[] = $line;
            }
        }

        return $output;
    }

    /**
     * Used to make all multiline variable process.
     *
     * @param bool     $multiline
     * @param string   $line
     * @param string[] $buffer
     *
     * @return array{bool,string,string[]}
     */
    private static function multilineProcess(bool $multiline, string $line, array $buffer)
    {
        // check if $line can be multiline variable
        if ($started = self::looksLikeMultilineStart($line)) {
            $multiline = true;
        }

        if ($multiline) {
            array_push($buffer, $line);

            if (self::looksLikeMultilineStop($line, $started)) {
                $multiline = false;
                $line = implode("\n", $buffer);
                $buffer = [];
            }
        }

        return [$multiline, $line, $buffer];
    }

    /**
     * Determine if the given line can be the start of a multiline variable.
     *
     * @param string $line
     *
     * @return bool
     */
    private static function looksLikeMultilineStart(string $line)
    {
        return Str::pos($line, '="')->map(function () use ($line) {
            return self::looksLikeMultilineStop($line, true) === false;
        })->getOrElse(false);
    }

    /**
     * Determine if the given line can be the start of a multiline variable.
     *
     * @param string $line
     * @param bool   $started
     *
     * @return bool
     */
    private static function looksLikeMultilineStop(string $line, bool $started)
    {
        if ($line === '"') {
            return true;
        }

        return self::getCharPairs(str_replace('\\\\', '', $line))->map(function (array $pairs) use ($started) {
            $seen = $started ? 0 : 1;

            foreach ($pairs as $pair) {
                if ($pair[0] !== '\\' && $pair[1] === '"') {
                    $seen++;
                }
            }

            return $seen > 1;
        })->success()->getOrElse(false);
    }

    /**
     * Get all pairs of adjacent characters within the line.
     *
     * @param string $line
     *
     * @return \GrahamCampbell\ResultType\Result<array{array{string,string|null}},string>
     */
    private static function getCharPairs(string $line)
    {
        return Str::split($line)->map(function (array $chars) {
            /** @var array{array{string,string|null}} */
            return array_map(null, $chars, array_slice($chars, 1));
        });
    }

    /**
     * Determine if the line in the file is a comment or whitespace.
     *
     * @param string $line
     *
     * @return bool
     */
    private static function isCommentOrWhitespace(string $line)
    {
        if (trim($line) === '') {
            return true;
        }

        $line = ltrim($line);

        return isset($line[0]) && $line[0] === '#';
    }
}
