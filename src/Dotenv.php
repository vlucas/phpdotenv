<?php
/**
 * Dotenv
 *
 * Loads a `.env` file in the given directory and sets the environment vars
 */
class Dotenv
{
    /**
     * Load `.env` file in given directory
     */
    public static function load($path, $file = '.env')
    {
        $filePath = rtrim($path, '/') . '/' . $file;
        if(!file_exists($filePath)) {
            throw new \InvalidArgumentException("Dotenv: Environment file .env not found. Create file with your environment settings at " . $filePath);
        }

        // Read file and get all lines
        $fc = file_get_contents($filePath);
        $lines = explode(PHP_EOL, $fc);

        foreach($lines as $line) {
            // Only use non-empty lines that look like setters
            if(!empty($line) && strpos($line, '=') !== false) {
                // Strip quotes because putenv can't handle them
                $line = trim(str_replace(array('\'', '"'), '', $line));

                putenv($line);

                // Set PHP superglobals
                list($key, $val) = explode('=', $line);
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

