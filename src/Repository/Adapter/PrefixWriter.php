<?php declare(strict_types=1);

namespace Dotenv\Repository\Adapter;

final class PrefixWriter implements WriterInterface
{
    /**
     * The inner writer to use.
     *
     * @var \Dotenv\Repository\Adapter\WriterInterface
     */
    private $writer;

    /**
     * Prefix to add before name.
     *
     * @var string
     */
    private $prefix;

    /**
     * Create a new prefix-writer instance.
     *
     * @param \Dotenv\Repository\Adapter\WriterInterface $writer
     * @param string                                     $prefix
     *
     * @return void
     */
    public function __construct(WriterInterface $writer, string $prefix)
    {
        $this->writer = $writer;
        $this->prefix = $prefix;
    }

    public function write(string $name, string $value)
    {
        $this->addPrefix($name);
        return $this->writer->write($name, $value);
    }

    public function delete(string $name)
    {
        $this->addPrefix($name);
        return $this->writer->delete($name);
    }

    private function addPrefix(string &$name): void
    {
        $name = $this->prefix . $name;
    }
}
