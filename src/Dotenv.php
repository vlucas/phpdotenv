<?php

namespace Dotenv;

/**
 * Dotenv.
 *
 * Loads a `.env` file in the given directory and sets the environment vars.
 */
class Dotenv
{
    /**
     * The file path.
     *
     * @var string
     */
    protected $filePath;

    /**
     * The loader instance.
     *
     * @var \Dotenv\Loader|null
     */
    protected $loader;

    public function __construct($path, $file = '.env')
    {
        $this->filePath = $this->getFilePath($path, $file);
    }

    /**
     * Load `.env` file in given directory.
     *
     * @return void
     */
    public function load()
    {
        $this->loader = new Loader($this->filePath, $immutable = true);

        return $this->loader->load();
    }

    /**
     * Load `.env` file in given directory.
     *
     * @return void
     */
    public function overload()
    {
        $this->loader = new Loader($this->filePath, $immutable = false);

        return $this->loader->load();
    }

    /**
     * Returns the full path to the file ensuring that it's readable.
     *
     * @param string $path
     * @param string $file
     *
     * @return string
     */
    protected function getFilePath($path, $file)
    {
        if (!is_string($file)) {
            $file = '.env';
        }

        $filePath = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$file;

        return $filePath;
    }

    /**
     * Required ensures that the specified variables exist, and returns a new Validation object.
     *
     * @param mixed $variable
     *
     * @return \Dotenv\Validator
     */
    public function required($variable)
    {
        return new Validator((array) $variable, $this->loader);
    }
}
