<?php declare(strict_types=1);

namespace Dotenv\Repository\Adapter;

final class PrefixReader implements ReaderInterface
{
    /**
     * The inner reader to use.
     *
     * @var \Dotenv\Repository\Adapter\ReaderInterface
     */
    private $reader;

    /**
     * Prefix to add before name.
     *
     * @var string
     */
    private $prefix;

    /**
     * Create a new prefix-reader instance.
     *
     * @param \Dotenv\Repository\Adapter\ReaderInterface    $reader
     * @param string                                        $prefix
     *
     * @return void
     */
    public function __construct(ReaderInterface $reader, string $prefix)
    {
        $this->reader = $reader;
        $this->prefix = $prefix;
    }

    public function read(string $name)
    {
        return $this->reader->read($this->prefix . $name);
    }
}
