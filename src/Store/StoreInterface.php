<?php

namespace Dotenv\Store;

interface StoreInterface
{
    /**
     * Read the content of the environment file(s).
     *
     * @throws \Dotenv\Exception\InvalidPathException
     *
     * @return string
     */
    public function read();
}
