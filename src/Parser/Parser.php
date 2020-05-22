<?php

declare(strict_types=1);

namespace Dotenv\Parser;

use Dotenv\Exception\InvalidFileException;
use Dotenv\Util\Regex;
use GrahamCampbell\ResultType\Result;
use GrahamCampbell\ResultType\Success;

final class Parser implements ParserInterface
{
    /**
     * Parse content into an entry array.
     *
     * @param string $content
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return \Dotenv\Parser\Entry[]
     */
    public function parse(string $content)
    {
        return Regex::split("/(\r\n|\n|\r)/", $content)->mapError(function () {
            return 'Could not split into separate lines.';
        })->flatMap(function (array $lines) {
            return self::process(Lines::process($lines));
        })->mapError(function (string $error) {
            throw new InvalidFileException(sprintf('Failed to parse dotenv file. %s', $error));
        })->success()->get();
    }

    /**
     * Convert the raw entries into proper entries.
     *
     * @param string[] $entries
     *
     * @return \GrahamCampbell\ResultType\Result<\Dotenv\Parser\Entry[],string>
     */
    private function process(array $entries)
    {
        return array_reduce($entries, function (Result $result, string $raw) {
            return $result->flatMap(function (array $entries) use ($raw) {
                return EntryParser::parse($raw)->map(function (Entry $entry) use ($entries) {
                    return array_merge($entries, [$entry]);
                });
            });
        }, Success::create([]));
    }
}
