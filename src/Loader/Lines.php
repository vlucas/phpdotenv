<?php

namespace Dotenv\Loader;

class Lines
{
    /**
     * Process the array of lines of environment variables.
     *
     * This will produce an array of entries, one per variable.
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
            list($multiline, $line, $multilineBuffer) = self::multilineProcess($multiline, $line, $multilineBuffer);

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
    private static function multilineProcess($multiline, $line, array $buffer)
    {
        $startsOnCurrentLine = $multiline ? false : self::looksLikeMultilineStart($line);

        // check if $line can be multiline variable
        if ($startsOnCurrentLine) {
            $multiline = true;
        }

        if ($multiline) {
            array_push($buffer, $line);

            if (self::looksLikeMultilineStop($line, $startsOnCurrentLine)) {
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
    private static function looksLikeMultilineStart($line)
    {
        if (strpos($line, '="') === false) {
            return false;
        }

        return self::looksLikeMultilineStop($line, true) === false;
    }

    /**
     * Determine if the given line can be the start of a multiline variable.
     *
     * @param string $line
     * @param bool   $started
     *
     * @return bool
     */
    private static function looksLikeMultilineStop($line, $started)
    {
        if ($line === '"') {
            return true;
        }

        $seen = $started ? 0 : 1;

        foreach (self::getCharPairs(str_replace('\\\\', '', $line)) as $pair) {
            if ($pair[0] !== '\\' && $pair[1] === '"') {
                $seen++;
            }
        }

        return $seen > 1;
    }

    /**
     * Get all pairs of adjacent characters within the line.
     *
     * @param string $line
     *
     * @return array{array{string,string|null}}
     */
    private static function getCharPairs($line)
    {
        $chars = str_split($line);

        /** @var array{array{string,string|null}} */
        return array_map(null, $chars, array_slice($chars, 1));
    }

    /**
     * Determine if the line in the file is a comment or whitespace.
     *
     * @param string $line
     *
     * @return bool
     */
    private static function isCommentOrWhitespace($line)
    {
        if (trim($line) === '') {
            return true;
        }

        $line = ltrim($line);

        return isset($line[0]) && $line[0] === '#';
    }
}
