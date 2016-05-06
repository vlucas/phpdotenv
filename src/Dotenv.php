<?php

namespace Dotenv;

/**
 * This is the dotenv class.
 *
 * It's responsible for loading a `.env` file in the given directory and
 * setting the environment vars.
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
     * Trim quoted variables?
     *
     * @var bool
     */
    protected $trim;

    /**
     * The loader instance.
     *
     * @var \Dotenv\Loader|null
     */
    protected $loader;

    /**
     * Create a new dotenv instance.
     *
     * @param string $path
     * @param string $file
     * @param bool   $trim
     *
     * @return void
     */
    public function __construct($path, $file = '.env', $trim = true)
    {
        $this->filePath = $this->getFilePath($path, $file);
        $this->trim = $trim;
        $this->loader = new Loader($this->filePath, true, $this->trim);
    }

    /**
     * Load `.env` file in given directory.
     *
     * @return array
     */
    public function load()
    {
        $this->loader = new Loader($this->filePath, true, $this->trim);

        return $this->loader->load();
    }

    /**
     * Load `.env` file in given directory.
     *
     * @return array
     */
    public function overload()
    {
        $this->loader = new Loader($this->filePath, false, $this->trim);

        return $this->loader->load();
    }

    /**
     * Returns the full path to the file.
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
     * Required ensures that the specified variables exist, and returns a new Validator object.
     *
     * @param string|string[] $variable
     *
     * @return \Dotenv\Validator
     */
    public function required($variable)
    {
        return new Validator((array) $variable, $this->loader);
    }
}
