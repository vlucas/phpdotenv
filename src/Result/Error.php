<?php

namespace Dotenv\Result;

use PhpOption\None;
use PhpOption\Some;

class Error extends Result
{
    /**
     * @var mixed
     */
    private $value;

    /**
     * Internal constructor for an error value.
     *
     * @param mixed $value
     *
     * @return void
     */
    private function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Create a new error value.
     *
     * @param mixed $value
     *
     * @return \Dotenv\Result\Result
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
        return None::create();
    }

    /**
     * Map over the success value.
     *
     * @param callable $f
     *
     * @return \Dotenv\Result\Result
     */
    public function mapSuccess(callable $f)
    {
        return self::create($this->value);
    }

    /**
     * Get the error option value.
     *
     * @return \PhpOption\Option
     */
    public function error()
    {
        return Some::create($this->value);
    }

    /**
     * Map over the error value.
     *
     * @param callable $f
     *
     * @return \Dotenv\Result\Result
     */
    public function mapError(callable $f)
    {
        return self::create($f($this->value));
    }
}
