<?php

if (!function_exists('dotenv')) {
    /**
     * Singleton that returns a dotenv instance.
     *
     * @staticvar \Dotenv\Dotenv the `Dotenv` instance
     * @return    \Dotenv\Dotenv the `Dotenv` instance
     */
    function dotenv()
    {
        static $dotenv = null;
        if (is_null($dotenv)) {
            $dotenv = new \Dotenv\Dotenv();
        }
        return $dotenv;
    }
}
