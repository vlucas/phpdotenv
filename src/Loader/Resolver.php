<?php

declare(strict_types=1);

namespace Dotenv\Loader;

use Dotenv\Parser\Value;
use Dotenv\Repository\RepositoryInterface;
use Dotenv\Util\Regex;
use Dotenv\Util\Str;
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
        $chars = $value->getChars();
        $vars = $value->getVars();

        if ($vars === []) {
            return $chars;
        }

        $tail = '';
        $cut = Str::len($chars);

        foreach ($vars as $i) {
            $segment = Str::substr($chars, $i, $cut - $i);
            $tail = self::resolveVariable($repository, $segment.$tail);
            $cut = $i;
        }

        return Str::substr($chars, 0, $cut).$tail;
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
            static function (array $matches) use ($repository) {
                /** @var string */
                return Option::fromValue($repository->get($matches[1]))->getOrElse($matches[0]);
            },
            $str,
            1
        )->success()->getOrElse($str);
    }
}
