<?php

namespace Dotenv;

/**
 * A Variable
 *
 * Represents a name value pair.
 * Handles creating from a line with format similar to name=value
 */
class Variable
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $value;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $name

     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param string $value
     * 
     * @return void
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}
