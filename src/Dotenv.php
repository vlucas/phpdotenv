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

    /**
     * @var Environment
     */
    private $environment;

    public function __construct($path, $file = '.env')
    {
        $this->filePath     = $this->getFilePath($path, $file);
        $this->loader       = new Loader($this->filePath);
        $this->environment  = new Environment();
    }

    /**
     * Load `.env` file in given directory.
     *
     * @return array
     */
    public function load()
    {
        $this->environment->setImmutable(true);
        return $this->parseLines();
    }

    /**
     * Load `.env` file in given directory.
     *
     * @return array
     */
    public function overload()
    {
        $this->environment->setImmutable(false);
        return $this->parseLines();
    }

    /**
     * @return array
     */
    private function parseLines()
    {
        $environment = $this->environment;
        return $this->loader->next(function ($line) use ($environment) {
            $environment->setVariable(new Variable($line));
        });
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
        return new Validator((array) $variable, $this->environment);
    }
}
