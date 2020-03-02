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
     * Set an environment variable.
     *
     * @param string      $name
     * @param string|null $value
     *
     * @return bool
     */
    public function set(string $name, string $value = null)
    {
        // Don't set non-whitelisted variables
        if (!$this->isWhitelisted($name)) {
            return false;
        }

        // Set the value on the inner writer
        return $this->writer->set($name, $value);
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
        // Don't clear non-whitelisted variables
        if (!$this->isWhitelisted($name)) {
            return false;
        }

        // Set the value on the inner writer
        return $this->writer->clear($name);
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
