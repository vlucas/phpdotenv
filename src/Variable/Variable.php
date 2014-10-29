<?php namespace Dotenv\Variable;

class Variable
{
    protected $name;
    protected $pendingValue;

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @param string $value
     */
    public function prepareValue($value)
    {
        $this->pendingValue = $value;
    }

    /**
     * @param bool $immutable
     * @return bool
     */
    public function commit($immutable = false)
    {
        if (!$this->passesMutabilityCheck($immutable)) {
            $this->pendingValue = null;
            return false;
        }

        $name = $this->name;
        $value = $this->pendingValue;

        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;

        return true;
    }

    /**
     * @return null|string
     */
    public function get()
    {
        $name = $this->name;
        if (array_key_exists($name, $_ENV)) {
            return $_ENV[$name];
        } elseif (array_key_exists($name, $_SERVER)) {
            return $_SERVER[$name];
        } else {
            return getenv($name) === false ? null : getenv($name);
        }
    }

    /**
     * @param array $allowedValues
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function required(array $allowedValues = array())
    {
        $value = $this->get();
        if (is_null($value)) {
            throw new \InvalidArgumentException(sprintf('%s missing', $this->name));
        }
        if (!empty($allowedValues) && !in_array($value, $allowedValues)) {
            throw new \InvalidArgumentException(sprintf('%s value not allowed (%s)', $this->name, $value));
        }

        return true;
    }

    /**
     * @param bool $immutable
     * @return bool true means it's ok to continue to write this variable
     */
    protected function passesMutabilityCheck($immutable)
    {
        return ($immutable === false || $this->get() === null);
    }
}
