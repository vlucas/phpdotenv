<?php

declare(strict_types=1);

namespace Dotenv\Loader;

use Dotenv\Regex\Regex;
use Dotenv\Repository\RepositoryInterface;
use PhpOption\Option;

final class Resolver
{
    /**
     * This class is a singleton.
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
     * @param \Dotenv\Loader\Value|null              $value
     *
     * @return string|null
     */
    public static function resolve(RepositoryInterface $repository, Value $value = null)
    {
        return Option::fromValue($value)
            ->map(function (Value $v) use ($repository) {
                return array_reduce($v->getVars(), function ($s, $i) use ($repository) {
                    return substr($s, 0, $i).self::resolveVariable($repository, substr($s, $i));
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
