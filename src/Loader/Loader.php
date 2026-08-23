<?php

declare(strict_types=1);

namespace Dotenv\Loader;

use Dotenv\Repository\RepositoryInterface;

final class Loader implements LoaderInterface
{
    /**
     * Load the given entries into the repository.
     *
     * We'll substitute any nested variables, and send each variable to the
     * repository, with the effect of actually mutating the environment.
     *
     * @param \Dotenv\Repository\RepositoryInterface $repository
     * @param \Dotenv\Parser\Entry[]                 $entries
     *
     * @return array<string, string|null>
     */
    public function load(RepositoryInterface $repository, array $entries)
    {
        $vars = [];

        foreach ($entries as $entry) {
            $name = $entry->getName();

            $value = $entry->getValue();

            if ($value->isDefined()) {
                $inner = Resolver::resolve($repository, $value->get());
                if ($repository->set($name, $inner)) {
                    $vars[$name] = $inner;
                }
            } elseif ($repository->clear($name)) {
                $vars[$name] = null;
            }
        }

        return $vars;
    }
}
