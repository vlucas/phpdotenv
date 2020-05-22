<?php

declare(strict_types=1);

namespace Dotenv\Parser;

final class Value
{
    /**
     * The string representation of the parsed value.
     *
     * @var string
     */
    private $chars;

    /**
     * The locations of the variables in the value.
     *
     * @var int[]
     */
    private $vars;

    /**
     * Internal constructor for a value.
     *
     * @param string $chars
     * @param int[]  $vars
     *
     * @return void
     */
    private function __construct(string $chars, array $vars)
    {
        $this->chars = $chars;
        $this->vars = $vars;
    }

    /**
     * Create an empty value instance.
     *
     * @return \Dotenv\Parser\Value
     */
    public static function blank()
    {
        return new self('', []);
    }

    /**
     * Create a new value instance, appending the character.
     *
     * @param string $char
     * @param bool   $var
     *
     * @return \Dotenv\Parser\Value
     */
    public function append(string $char, bool $var)
    {
        return new self(
            $this->chars.$char,
            $var ? array_merge($this->vars, [mb_strlen($this->chars)]) : $this->vars
        );
    }

    /**
     * Get the string representation of the parsed value.
     *
     * @return string
     */
    public function getChars()
    {
        return $this->chars;
    }

    /**
     * Get the locations of the variables in the value.
     *
     * @return int[]
     */
    public function getVars()
    {
        $vars = $this->vars;

        rsort($vars);

        return $vars;
    }
}
