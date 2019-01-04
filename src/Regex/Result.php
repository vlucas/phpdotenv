<?php

namespace Dotenv\Regex;

abstract class Result
{
    /**
     * Get the success option value.
     *
     * @return \PhpOption\Option
     */
    public abstract function success();

    /**
     * Get the error value, if possible.
     *
     * @return string
     */
    public function getSuccess()
    {
        return $this->success()->get();
    }

    /**
     * Map over the success value.
     *
     * @param callable $f
     *
     * @return \Dotenv\Regex\Result
     */
    public abstract function mapSuccess(callable $f);

    /**
     * Get the error option value.
     *
     * @return \PhpOption\Option
     */
    public abstract function error();

    /**
     * Get the error value, if possible.
     *
     * @return string
     */
    public function getError()
    {
        return $this->error()->get();
    }

    /**
     * Map over the error value.
     *
     * @param callable $f
     *
     * @return \Dotenv\Regex\Result
     */
    public abstract function mapError(callable $f);
}
