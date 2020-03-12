<?php

declare(strict_types=1);

namespace Dotenv\Repository\Adapter;

final class WhitelistWriter implements WriterInterface
{
    /**
     * The inner writer to use.
     *
     * @var \Dotenv\Repository\Adapter\WriterInterface
     */
    private $writer;

    /**
     * The variable name whitelist.
     *
     * @var string[]
     */
    private $whitelist;

    /**
     * Create a new whitelist writer instance.
     *
     * @param \Dotenv\Repository\Adapter\WriterInterface $writer
     * @param string[]                                   $whitelist
     *
     * @return void
     */
    public function __construct(WriterInterface $writer, array $whitelist)
    {
        $this->writer = $writer;
        $this->whitelist = $whitelist;
    }

    /**
     * Write to an environment variable, if possible.
     *
     * @param string $name
     * @param string $value
     *
     * @return bool
     */
    public function write(string $name, string $value)
    {
        // Don't set non-whitelisted variables
        if (!$this->isWhitelisted($name)) {
            return false;
        }

        // Set the value on the inner writer
        return $this->writer->write($name, $value);
    }

    /**
     * Delete an environment variable, if possible.
     *
     * @param string $name
     *
     * @return bool
     */
    public function delete(string $name)
    {
        // Don't clear non-whitelisted variables
        if (!$this->isWhitelisted($name)) {
            return false;
        }

        // Set the value on the inner writer
        return $this->writer->delete($name);
    }

    /**
     * Determine if the given variable is whitelisted.
     *
     * @param string $name
     *
     * @return bool
     */
    private function isWhitelisted(string $name)
    {
        return in_array($name, $this->whitelist, true);
    }
}
