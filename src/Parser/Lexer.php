<?php

declare(strict_types=1);

namespace Dotenv\Parser;

use RuntimeException;

final class Lexer
{
    /**
     * The regex for each type of token.
     *
     * @var string
     */
    private const PATTERNS = [
        '[\r\n]{1,1000}', '[^\S\r\n]{1,1000}', '\\\\', '\'', '"', '\\#', '\\$', '([^(\s\\\\\'"\\#\\$)]|\\(|\\)){1,1000}'
    ];

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
     * Convert a value into an array of tokens.
     *
     * Multibyte string processing is not needed here, and nether is error
     * handling, for performance reasons.
     *
     * @param string $value
     *
     * @return string[]
     */
    public static function lex(string $value)
    {
        static $regex;

        if ($regex === null) {
            $regex = '(('.implode(')|(', self::PATTERNS).'))A';
        }

        $tokens = [];

        $offset = 0;

        while (isset($value[$offset])) {
            if (!preg_match($regex, $value, $matches, 0, $offset)) {
                throw new RuntimeException(sprintf('Lexer encountered unexpected character [%s].', $value[$offset]));
            }

            for ($i = 1; $matches[$i] === ''; ++$i);

            $tokens[] = $matches[0];

            $offset += strlen($matches[0]);
        }

        return $tokens;
    }
}
