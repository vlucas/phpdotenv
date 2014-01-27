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
        if(!file_exists($filePath) || !is_file($filePath)) {
            throw new \InvalidArgumentException("Dotenv: Environment file .env not found. Create file with your environment settings at " . $filePath);
        }

        // Read file into an array of lines with auto-detected line endings
        $autodetect = ini_get('auto_detect_line_endings');
        ini_set( 'auto_detect_line_endings', '1' );
        $lines = file($filePath, FILE_SKIP_EMPTY_LINES);
        ini_set( 'auto_detect_line_endings', $autodetect );

        foreach($lines as $line) {
            // Only use non-empty lines that look like setters
            if(strpos($line, '=') !== false) {
                // Strip quotes because putenv can't handle them. Also remove 'export' if present
                $line = str_replace(array('export ', '\'', '"'), '', $line);
                // Remove whitespaces around key & value
                list( $key, $val ) = array_map( 'trim', explode('=', $line, 2) );

                putenv("$key=$val");
                // Set PHP superglobals
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

