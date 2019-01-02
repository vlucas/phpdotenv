<?php

namespace Dotenv;

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

            if (!$multiline && !self::isComment($line) && self::looksLikeSetter($line)) {
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
     * @return string[]
     */
    private static function multilineProcess($multiline, $line, array $buffer)
    {
        // check if $line can be multiline variable
        if (self::looksLikeMultilineStart($line)) {
            $multiline = true;
        }

        if ($multiline) {
            array_push($buffer, $line);

            if (self::looksLikeMultilineStop($line)) {
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
        return strpos($line, '="') !== false && substr_count($line, '"') === 1;
    }

    /**
     * Determine if the given line can be the start of a multiline variable.
     *
     * @param string $line
     *
     * @return bool
     */
    private static function looksLikeMultilineStop($line)
    {
        return strpos($line, '"') !== false && substr_count($line, '="') === 0;
    }

    /**
     * Determine if the line in the file is a comment, e.g. begins with a #.
     *
     * @param string $line
     *
     * @return bool
     */
    private static function isComment($line)
    {
        $line = ltrim($line);

        return isset($line[0]) && $line[0] === '#';
    }

    /**
     * Determine if the given line looks like it's setting a variable.
     *
     * @param string $line
     *
     * @return bool
     */
    private static function looksLikeSetter($line)
    {
        return strpos($line, '=') !== false;
    }
}
