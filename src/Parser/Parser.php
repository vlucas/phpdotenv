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
        return Regex::split("/(\r\n|\n|\r)/", $content)->mapError(static fn () => 'Could not split into separate lines.')->flatMap(static fn (array $lines) => self::process(Lines::process($lines)))->mapError(static function (string $error) {
            throw new InvalidFileException(\sprintf('Failed to parse dotenv file. %s', $error));
        })->success()->get();
    }

    /**
     * Convert the raw entries into proper entries.
     *
     * @param string[] $entries
     *
     * @return \GrahamCampbell\ResultType\Result<\Dotenv\Parser\Entry[],string>
     */
    private static function process(array $entries)
    {
        /** @var \GrahamCampbell\ResultType\Result<\Dotenv\Parser\Entry[],string> */
        return \array_reduce($entries, static fn (Result $result, string $raw) => $result->flatMap(static fn (array $entries) => EntryParser::parse($raw)->map(static fn (Entry $entry) => \array_merge($entries, [$entry]))), Success::create([]));
    }
}
