<?php

namespace Dotenv\Loader;

class Value
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
    private function __construct($chars, array $vars)
    {
        $this->chars = $chars;
        $this->vars = $vars;
    }

    /**
     * Create an empty value instance.
     *
     * @return \Dotenv\Loader\Value
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
     * @return \Dotenv\Loader\Value
     */
    public function append($char, $var)
    {
        return new self(
            $this->chars.$char,
            $var ? array_merge($this->vars, [strlen($this->chars)]) : $this->vars
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
