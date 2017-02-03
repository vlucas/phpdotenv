<?php

use Dotenv\Dotenv;

if (! function_exists('create_env'))
{
    /**
     * Helper function to register a new environment.
     *
     * @param string $directory
     * @return \Dotenv\Dotenv
     */
    function create_env($directory)
    {
        $dotenv = new Dotenv($directory);

        $dotenv->load();

        return $dotenv;
    }
}