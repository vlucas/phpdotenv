<?php

namespace Dotenv\Exception;

use InvalidArgumentException;

abstract class AbstractFileException extends InvalidArgumentException
{
    /**
     * The file path.
     *
     * @var string
     */
    protected $filePath;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;

        parent::__construct($this->generateMessage());
    }

    /**
     * Generate error message.
     *
     * @return string
     */
    abstract protected function generateMessage();

    /**
     * Return the file path that error occurred.
     *
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }
}
