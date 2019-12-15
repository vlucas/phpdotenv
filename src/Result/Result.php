<?php

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
     * Get the success value, if possible.
     *
     * @throws \RuntimeException
     *
     * @return T
     */
    public function getSuccess()
    {
        return $this->success()->get();
    }

    /**
     * Map over the success value.
     *
     * @template S
     *
     * @param callable(T):S $f
     *
     * @return \Dotenv\Result\Result<S,E>
     */
    abstract public function mapSuccess(callable $f);

    /**
     * Get the error option value.
     *
     * @return \PhpOption\Option<E>
     */
    abstract public function error();

    /**
     * Get the error value, if possible.
     *
     * @throws \RuntimeException
     *
     * @return E
     */
    public function getError()
    {
        return $this->error()->get();
    }

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
