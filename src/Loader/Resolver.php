<?php

declare(strict_types=1);

namespace Dotenv\Loader;

use Dotenv\Parser\Value;
use Dotenv\Regex\Regex;
use Dotenv\Repository\RepositoryInterface;
use PhpOption\Option;

final class Resolver
{
    /**
     * This class is a singleton.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    private function __construct()
    {
        //
    }

    /**
     * Resolve the nested variables in the given value.
     *
     * Replaces ${varname} patterns in the allowed positions in the variable
     * value by an existing environment variable.
     *
     * @param \Dotenv\Repository\RepositoryInterface $repository
     * @param \Dotenv\Parser\Value                   $value
     *
     * @return string
     */
    public static function resolve(RepositoryInterface $repository, Value $value)
    {
        return array_reduce($value->getVars(), function ($s, $i) use ($repository) {
            return mb_substr($s, 0, $i).self::resolveVariable($repository, mb_substr($s, $i));
        }, $value->getChars());
    }

    /**
     * Resolve a single nested variable.
     *
     * @param \Dotenv\Repository\RepositoryInterface $repository
     * @param string                                 $str
     *
     * @return string
     */
    private static function resolveVariable(RepositoryInterface $repository, string $str)
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
