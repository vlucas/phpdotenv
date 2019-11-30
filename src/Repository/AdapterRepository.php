<?php

namespace Dotenv\Repository;

class AdapterRepository extends AbstractRepository
{
    /**
     * The set of readers to use.
     *
     * @var \Dotenv\Repository\Adapter\ReadersInterface[]
     */
    protected $readers;

    /**
     * The set of writers to use.
     *
     * @var \Dotenv\Repository\Adapter\WritersInterface[]
     */
    protected $writers;

    /**
     * Create a new dotenv environment variables instance.
     *
     * @param \Dotenv\Repository\Adapter\ReaderInterface[] $readers
     * @param \Dotenv\Repository\Adapter\WriterInterface[] $writers
     * @param bool                                          $immutable
     *
     * @return void
     */
    public function __construct(array $readers, array $writers, $immutable)
    {
        $this->readers = $readers;
        $this->writers = $writers;
        parent::__construct($immutable);
    }

    /**
     * Get an environment variable.
     *
     * We do this by querying our readers sequentially.
     *
     * @param string $name
     *
     * @return string|null
     */
    protected function getInternal($name)
    {
        foreach ($this->readers as $reader) {
            $result = $reader->get($name);
            if ($result->isDefined()) {
                return $result->get();
            }
        }
    }

    /**
     * Set an environment variable.
     *
     * @param string      $name
     * @param string|null $value
     *
     * @return void
     */
    protected function setInternal($name, $value = null)
    {
        foreach ($this->writers as $writers) {
            $writers->set($name, $value);
        }
    }

    /**
     * Clear an environment variable.
     *
     * @param string $name
     *
     * @return void
     */
    protected function clearInternal($name)
    {
        foreach ($this->writers as $writers) {
            $writers->clear($name);
        }
    }
}
