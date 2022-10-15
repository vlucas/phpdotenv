<?php

namespace Dotenv\Repository;

class AdapterRepository extends AbstractRepository
{
    /**
     * The set of readers to use.
     *
     * @var \Dotenv\Repository\Adapter\ReaderInterface[]
     */
    protected $readers;

    /**
     * The set of writers to use.
     *
     * @var \Dotenv\Repository\Adapter\WriterInterface[]
     */
    protected $writers;

    /**
     * Create a new adapter repository instance.
     *
     * @param \Dotenv\Repository\Adapter\ReaderInterface[] $readers
     * @param \Dotenv\Repository\Adapter\WriterInterface[] $writers
     * @param bool                                         $immutable
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
     * @param non-empty-string $name
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

        return null;
    }

    /**
     * Set an environment variable.
     *
     * @param non-empty-string $name
     * @param string|null      $value
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
     * @param non-empty-string $name
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
