<?php

declare(strict_types=1);

namespace Dotenv\Repository\Adapter;

final class MultiWriter implements WriterInterface
{
    /**
     * The set of writers to use.
     *
     * @var \Dotenv\Repository\Adapter\WriterInterface[]
     */
    private $writers;

    /**
     * Create a new multi-writer instance.
     *
     * @param \Dotenv\Repository\Adapter\WriterInterface[] $writers
     *
     * @return void
     */
    public function __construct(array $writers)
    {
        $this->writers = $writers;
    }

    /**
     * Set an environment variable.
     *
     * @param string      $name
     * @param string|null $value
     *
     * @return bool
     */
    public function set(string $name, string $value = null)
    {
        foreach ($this->writers as $writers) {
            if (!$writers->set($name, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Clear an environment variable.
     *
     * @param string $name
     *
     * @return bool
     */
    public function clear(string $name)
    {
        foreach ($this->writers as $writers) {
            if (!$writers->clear($name)) {
                return false;
            }
        }

        return true;
    }
}
