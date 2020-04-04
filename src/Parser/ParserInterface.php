<?php

declare(strict_types=1);

namespace Dotenv\Parser;

interface ParserInterface
{
    /**
     * Parse a raw entry into a proper entry.
     *
     * @param string $entry
     *
     * @return \GrahamCampbell\ResultType\Result<\Dotenv\Parser\Entry,string>
     */
    public function parse(string $entry);

    /**
     * Parse the given variable name.
     *
     * @param string $name
     *
     * @return \GrahamCampbell\ResultType\Result<string,string>
     */
    public function parseName(string $name);

    /**
     * Parse the given variable value.
     *
     * @param string $value
     *
     * @return \GrahamCampbell\ResultType\Result<\Dotenv\Parser\Value,string>
     */
    public function parseValue(string $value);
}
