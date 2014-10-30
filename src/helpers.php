<?php

if (!function_exists('dotenv')) {
    /**
     * Singleton that returns a dotenv instance.
     *
     * @return \Dotenv\Dotenv
     */
    function dotenv()
    {
        static $dotenv;
        if (is_null($dotenv)) {
            $dotenv = new \Dotenv\Dotenv();
        }
        return $dotenv;
    }
}
