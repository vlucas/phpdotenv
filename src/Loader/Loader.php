<?php

declare(strict_types=1);

namespace Dotenv\Loader;

use Dotenv\Exception\InvalidFileException;
use Dotenv\Regex\Regex;
use Dotenv\Repository\RepositoryInterface;
use Dotenv\Result\Result;
use Dotenv\Result\Success;
use PhpOption\Option;

final class Loader implements LoaderInterface
{
    /**
     * The variable name whitelist.
     *
     * @var string[]|null
     */
    private $whitelist;

    /**
     * Create a new loader instance.
     *
     * @param string[]|null $whitelist
     *
     * @return void
     */
    public function __construct(array $whitelist = null)
    {
        $this->whitelist = $whitelist;
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
     * We'll fill out any nested variables, and acually set the variable using
     * the underlying environment variables instance.
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

                    if ($this->whitelist === null || in_array($name, $this->whitelist, true)) {
                        $value = self::resolveNestedVariables($repository, $value);
                        $repository->set($name, $value);

                        return array_merge($vars, [$name => $value]);
                    }

                    return $vars;
                });
            });
        }, Success::create([]));
    }

    /**
     * Resolve the nested variables.
     *
     * Replaces ${varname} patterns in the allowed positions in the variable
     * value by an existing environment variable.
     *
     * @param \Dotenv\Repository\RepositoryInterface $repository
     * @param \Dotenv\Loader\Value|null              $value
     *
     * @return string|null
     */
    private static function resolveNestedVariables(RepositoryInterface $repository, Value $value = null)
    {
        return Option::fromValue($value)
            ->map(function (Value $v) use ($repository) {
                return array_reduce($v->getVars(), function ($s, $i) use ($repository) {
                    return substr($s, 0, $i).self::resolveNestedVariable($repository, substr($s, $i));
                }, $v->getChars());
            })
            ->getOrElse(null);
    }

    /**
     * Resolve a single nested variable.
     *
     * @param \Dotenv\Repository\RepositoryInterface $repository
     * @param string                                 $str
     *
     * @return string
     */
    private static function resolveNestedVariable(RepositoryInterface $repository, string $str)
    {
        return Regex::replaceCallback(
            '/\A\${([a-zA-Z0-9_.]+)}/',
            function (array $matches) use ($repository) {
                return Option::fromValue($repository->get($matches[1]))
                    ->getOrElse($matches[0]);
            },
            $str,
            1
        )->success()->getOrElse($str);
    }
}
