<?php

namespace Dotenv\Result;

use PhpOption\None;
use PhpOption\Some;

/**
 * @template T
 * @template E
 * @extends \Dotenv\Result\Result<T,E>
 */
class Success extends Result
{
    /**
     * @var T
     */
    private $value;

    /**
     * Internal constructor for a success value.
     *
     * @param T $value
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
     * @param T $value
     *
     * @return \Dotenv\Result\Result<T,E>
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
        return Some::create($this->value);
    }

    /**
     * Map over the success value.
     *
     * @param callable $f
     *
     * @return \Dotenv\Result\Result<T,E>
     */
    public function mapSuccess(callable $f)
    {
        return self::create($f($this->value));
    }

    /**
     * Get the error option value.
     *
     * @return \PhpOption\Option<E>
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
     * @return \Dotenv\Result\Result<T,E>
     */
    public function mapError(callable $f)
    {
        return self::create($this->value);
    }
}
