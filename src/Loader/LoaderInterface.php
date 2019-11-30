<?php

namespace Dotenv\Loader;

use Dotenv\Repository\RepositoryInterface;

interface LoaderInterface
{
    /**
     * Load the given environment file content into the repository.
     *
     * @param \Dotenv\Repository\RepositoryInterface $repository
     * @param string                                 $content
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return array<string|null>
     */
    public function load(RepositoryInterface $repository, $content);
}
