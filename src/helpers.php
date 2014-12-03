<?php

if (!function_exists('dotenv')) {
    /**
     * Singleton that returns a dotenv instance.
     *
     * @staticvar \Dotenv\Dotenv
     *
     * @return \Dotenv\Dotenv
     */
    function dotenv()
    {
        static $dotenv = null;
        if ($dotenv === null) {
            $dotenv = new \Dotenv\Dotenv();
        }

        return $dotenv;
    }
}
