<?php

if (!function_exists('dotenv')) {
    /**
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
