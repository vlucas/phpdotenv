<?php

namespace Dotenv;

use InvalidArgumentException;

/**
 * Loader.
 *
 * Loads Variables by reading a file from disk and:
 * - stripping comments beginning with a `#`
 * - parsing lines that look shell variable setters, e.g `export key = value`, `key="value"`
 */
class Loader
{
    /**
     * The file path.
     *
     * @var string
     */
    protected $filePath;

    /**
     * Create a new loader instance.
     *
     * @param string $filePath
     */
    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Load `.env` file in given directory.
     *
     * @return array
     */
    public function next($callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Callback must be callable');
        }

        $this->ensureFileIsReadable();

        $filePath = $this->filePath;
        $lines = $this->readLinesFromFile($filePath);
        foreach ($lines as $line) {
            if ($this->isComment($line)) {
                continue;
            }
            if ($this->looksLikeSetter($line)) {
                $callback($line);
            }
        }

        return $lines;
    }

    /**
     * Ensures the given filePath is readable.
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    protected function ensureFileIsReadable()
    {
        $filePath = $this->filePath;
        if (!is_readable($filePath) || !is_file($filePath)) {
            throw new InvalidArgumentException(sprintf(
                'Dotenv: Environment file .env not found or not readable. '.
                'Create file with your environment settings at %s',
                $filePath
            ));
        }
    }

    /**
     * Read lines from the file, auto detecting line endings.
     *
     * @param string $filePath
     *
     * @return array
     */
    protected function readLinesFromFile($filePath)
    {
        // Read file into an array of lines with auto-detected line endings
        $autodetect = ini_get('auto_detect_line_endings');
        ini_set('auto_detect_line_endings', '1');
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        ini_set('auto_detect_line_endings', $autodetect);

        return $lines;
    }

    /**
     * Determine if the line in the file is a comment, e.g. begins with a #.
     *
     * @param string $line
     *
     * @return bool
     */
    protected function isComment($line)
    {
        return strpos(trim($line), '#') === 0;
    }

    /**
     * Determine if the given line looks like it's setting a variable.
     *
     * @param string $line
     *
     * @return bool
     */
    protected function looksLikeSetter($line)
    {
        return strpos($line, '=') !== false;
    }
}
