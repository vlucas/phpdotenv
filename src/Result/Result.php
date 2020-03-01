<?php

declare(strict_types=1);

namespace Dotenv\Result;

/**
 * @template T
 * @template E
 */
abstract class Result
{
    /**
     * Get the success option value.
     *
     * @return \PhpOption\Option<T>
     */
    abstract public function success();

    /**
     * Map over the success value.
     *
     * @template S
     *
     * @param callable(T):S $f
     *
     * @return \Dotenv\Result\Result<S,E>
     */
    abstract public function map(callable $f);

    /**
     * Flat map over the success value.
     *
     * @template S
     * @template F
     *
     * @param callable(T):\Dotenv\Result\Result<S,F> $f
     *
     * @return \Dotenv\Result\Result<S,F>
     */
    abstract public function flatMap(callable $f);

    /**
     * Get the error option value.
     *
     * @return \PhpOption\Option<E>
     */
    abstract public function error();

    /**
     * Map over the error value.
     *
     * @template F
     *
     * @param callable(E):F $f
     *
     * @return \Dotenv\Result\Result<T,F>
     */
    abstract public function mapError(callable $f);
}
