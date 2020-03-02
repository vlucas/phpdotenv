<?php

declare(strict_types=1);

namespace Dotenv\Loader;

use Dotenv\Exception\InvalidFileException;
use Dotenv\Regex\Regex;
use Dotenv\Repository\RepositoryInterface;
use Dotenv\Result\Result;
use Dotenv\Result\Success;

final class Loader implements LoaderInterface
{
    /**
     * Load the given environment file content into the repository.
     *
     * @param \Dotenv\Repository\RepositoryInterface $repository
     * @param string                                 $content
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return array<string,string|null>
     */
    public function load(RepositoryInterface $repository, string $content)
    {
        $variables = $this->processEntries(
            $repository,
            Lines::process(Regex::split("/(\r\n|\n|\r)/", $content)->success()->get())
        );

        return $variables->mapError(function (string $error) {
            throw new InvalidFileException($error);
        })->success()->get();
    }

    /**
     * Process the environment variable entries.
     *
     * We'll handover each entry to the parser, then substitute any nested
     * variables, and set each variable on the repository instance, with the
     * effect of actually mutating the environment.
     *
     * @param \Dotenv\Repository\RepositoryInterface $repository
     * @param string[]                               $entries
     *
     * @return \Dotenv\Result\Result<array<string,string|null>,string>
     */
    private function processEntries(RepositoryInterface $repository, array $entries)
    {
        return array_reduce($entries, function (Result $vars, string $entry) use ($repository) {
            return $vars->flatMap(function (array $vars) use ($repository, $entry) {
                return Parser::parse($entry)->map(function (array $parsed) use ($repository, $vars) {
                    [$name, $value] = $parsed;

                    $resolved = Resolver::resolve($repository, $value);
                    
                    if ($repository->set($name, $resolved)) {
                        return array_merge($vars, [$name => $resolved]);
                    }

                    return $vars;
                });
            });
        }, Success::create([]));
    }
}
