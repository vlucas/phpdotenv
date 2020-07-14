<?php

namespace Dotenv\Loader;

use Dotenv\Regex\Regex;
use Dotenv\Repository\RepositoryInterface;
use PhpOption\Option;

class Loader implements LoaderInterface
{
    /**
     * The variable name whitelist.
     *
     * @var string[]|null
     */
    protected $whitelist;

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
    public function load(RepositoryInterface $repository, $content)
    {
        return $this->processEntries(
            $repository,
            Lines::process(Regex::split("/(\r\n|\n|\r)/", $content)->getSuccess())
        );
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
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return array<string,string|null>
     */
    private function processEntries(RepositoryInterface $repository, array $entries)
    {
        $vars = [];

        foreach ($entries as $entry) {
            list($name, $value) = Parser::parse($entry);
            if ($this->whitelist === null || in_array($name, $this->whitelist, true)) {
                $vars[$name] = self::resolveNestedVariables($repository, $value);
                $repository->set($name, $vars[$name]);
            }
        }

        return $vars;
    }

    /**
     * Resolve the nested variables.
     *
     * Look for ${varname} patterns in the variable value and replace with an
     * existing environment variable.
     *
     * @param \Dotenv\Repository\RepositoryInterface $repository
     * @param \Dotenv\Loader\Value|null              $value
     *
     * @return string|null
     */
    private static function resolveNestedVariables(RepositoryInterface $repository, Value $value = null)
    {
        /** @var Option<Value> */
        $option = Option::fromValue($value);

        return $option
            ->map(function (Value $v) use ($repository) {
                /** @var string */
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
    private static function resolveNestedVariable(RepositoryInterface $repository, $str)
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
