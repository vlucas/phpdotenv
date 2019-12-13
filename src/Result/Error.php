<?php

namespace Dotenv\Result;

use PhpOption\None;
use PhpOption\Some;

/**
 * @template T
 * @template E
 * @extends \Dotenv\Result\Result<T,E>
 */
class Error extends Result
{
    /**
     * @var E
     */
    private $value;

    /**
     * Internal constructor for an error value.
     *
     * @param E $value
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
     * @template F
     *
     * @param F $value
     *
     * @return \Dotenv\Result\Result<T,F>
     */
    public static function create($value)
    {
        return new self($value);
    }

    /**
     * Get the success option value.
     *
     * @return \PhpOption\Option<T>
     */
    public function success()
    {
        return None::create();
    }

    /**
     * Map over the success value.
     *
     * @template S
     *
     * @param callable(T): S $f
     *
     * @return \Dotenv\Result\Result<S,E>
     */
    public function mapSuccess(callable $f)
    {
        return self::create($this->value);
    }

    /**
     * Get the error option value.
     *
     * @return \PhpOption\Option<E>
     */
    public function error()
    {
        return Some::create($this->value);
    }

    /**
     * Map over the error value.
     *
     * @template F
     *
     * @param callable(E): F $f
     *
     * @return \Dotenv\Result\Result<T,F>
     */
    public function mapError(callable $f)
    {
        return self::create($f($this->value));
    }
}
