<?php

namespace Dotenv;

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
     * @return \Dotenv\Value
     */
    public static function blank()
    {
        return new Value('', []);
    }

    /**
     * Create a new value instance, appending the character.
     *
     * @param string $char
     * @param bool   $var
     *
     * @return \Dotenv\Value
     */
    public function append($char, $var)
    {
        return new Value(
            $this->chars.$char,
            $var ? array_merge($this->vars, [strlen($this->chars)]) : $this->vars
        );
    }

    public function getChars()
    {
        return $this->chars;
    }

    public function getVars()
    {
        $vars = $this->vars;
        rsort($vars);

        return $vars;
    }
}
