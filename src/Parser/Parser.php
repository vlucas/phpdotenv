<?php

declare(strict_types=1);

namespace Dotenv\Parser;

use Dotenv\Exception\InvalidFileException;
use Dotenv\Util\Regex;
use GrahamCampbell\ResultType\Error;
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
        return Regex::split("/(\r\n|\n|\r)/", $content)->mapError(static function () {
            return 'Could not split into separate lines.';
        })->flatMap(static function (array $lines) {
            return self::process(Lines::process($lines));
        })->mapError(static function (string $error) {
            throw new InvalidFileException(\sprintf('Failed to parse dotenv file. %s', $error));
        })->success()->get();
    }

    /**
     * Convert the raw entries into proper entries.
     *
     * @param string[] $entries
     *
     * @return \GrahamCampbell\ResultType\Result<\Dotenv\Parser\Entry[], string>
     */
    private static function process(array $entries)
    {
        /** @var \Dotenv\Parser\Entry[] */
        $output = [];

        foreach ($entries as $raw) {
            $entry = EntryParser::parse($raw);

            if ($entry->error()->isDefined()) {
                return Error::create($entry->error()->get());
            }

            $output[] = $entry->success()->get();
        }

        return Success::create($output);
    }
}
