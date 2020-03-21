<?php

declare(strict_types=1);

namespace Dotenv\Loader;

use Dotenv\Exception\InvalidFileException;
use Dotenv\Parser\Entry;
use Dotenv\Parser\Value;
use Dotenv\Parser\ParserInterface;
use Dotenv\Regex\Regex;
use Dotenv\Repository\RepositoryInterface;
use GrahamCampbell\ResultType\Result;
use GrahamCampbell\ResultType\Success;

final class Loader implements LoaderInterface
{
    /**
     * The entity parser instance.
     *
     * @var \Dotenv\Parser\ParserInterface
     */
    private $parser;

    /**
     * Create a new loader instance.
     *
     * @param \Dotenv\Parser\ParserInterface $parser
     *
     * @return void
     */
    public function __construct(ParserInterface $parser)
    {
        $this->parser = $parser;
    }

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
     * @return \GrahamCampbell\ResultType\Result<array<string,string|null>,string>
     */
    private function processEntries(RepositoryInterface $repository, array $entries)
    {
        return array_reduce($entries, function (Result $vars, string $raw) use ($repository) {
            return $vars->flatMap(function (array $vars) use ($repository, $raw) {
                return $this->parser->parse($raw)->map(function (Entry $entry) use ($repository, $vars) {
                    $name = $entry->getName();

                    $value = $entry->getValue()->map(function (Value $value) use ($repository) {
                        return Resolver::resolve($repository, $value);
                    });

                    if ($value->isDefined()) {
                        $inner = $value->get();
                        if ($repository->set($name, $inner)) {
                            return array_merge($vars, [$name => $inner]);
                        }
                    } else {
                        if ($repository->clear($name)) {
                            return array_merge($vars, [$name => null]);
                        }
                    }

                    return $vars;
                });
            });
        }, Success::create([]));
    }
}
