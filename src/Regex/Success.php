<?php

namespace Dotenv\Regex;

use PhpOption\None;
use PhpOption\Some;

class Success extends Result
{
    /**
     * @var string|int
     */
    private $value;

    /**
     * Internal constructor for a success value.
     *
     * @param string|int $value
     *
     * @return void
     */
    private function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Create a new success value.
     *
     * @param string|int $value
     *
     * @return \Dotenv\Regex\Result
     */
    public static function create($value)
    {
        return new self($value);
    }

    /**
     * Get the success option value.
     *
     * @return \PhpOption\Option
     */
    public function success()
    {
        return Some::create($this->value);
    }

    /**
     * Map over the success value.
     *
     * @param callable $f
     *
     * @return \Dotenv\Regex\Result
     */
    public function mapSuccess(callable $f)
    {
        return self::create($f($this->value));
    }

    /**
     * Get the error option value.
     *
     * @return \PhpOption\Option
     */
    public function error()
    {
        return None::create();
    }

    /**
     * Map over the error value.
     *
     * @param callable $f
     *
     * @return \Dotenv\Regex\Result
     */
    public function mapError(callable $f)
    {
        return self::create($this->value);
    }
}
