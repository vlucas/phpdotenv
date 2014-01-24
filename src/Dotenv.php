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
        if(!is_string($file)) {
            $file = '.env';
        }

        $filePath = rtrim($path, '/') . '/' . $file;
        if(!file_exists($filePath)) {
            throw new \InvalidArgumentException("Dotenv: Environment file .env not found. Create file with your environment settings at " . $filePath);
        }

        // Read file and get all lines
        $fc = file_get_contents($filePath);
        $lines = preg_split('/\r\n|\r|\n/', $fc);

        foreach($lines as $line) {
            // Only use non-empty lines that look like setters
            if(!empty($line) && strpos($line, '=') !== false) {
                // Standardize to remove spaces around equals if they're there
                $line = preg_replace('/( )?=( )?/', '=', $line);

                // Strip quotes because putenv can't handle them. Also remove 'export' if present
                $line = trim(str_replace(array('export ', '\'', '"'), '', $line));

                putenv($line);

                // Set PHP superglobals
                list($key, $val) = explode('=', $line, 2);
                $key = trim($key);
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }

    /**
     * Require specified ENV vars to be present, or throw Exception
     *
     * @throws \RuntimeException
     */
    public static function required($env)
    {
        $envs = (array) $env;
        $missingEnvs = array();

        foreach($envs as $env) {
            // Check $_SERVER in addition to ENV
            if(!isset($_SERVER[$env]) || getenv($env) === false) {
                $missingEnvs[] = $env;
            }
        }

        if(!empty($missingEnvs)) {
            throw new \RuntimeException("Required ENV vars missing: '" . implode("', '", $missingEnvs) . "'");
        }

        return true;
    }
}

