<?php

declare(strict_types=1);

namespace Dotenv\Tests\Parser;

use Dotenv\Exception\InvalidFileException;
use Dotenv\Parser\Entry;
use Dotenv\Parser\Parser;
use Dotenv\Parser\ParserInterface;
use Dotenv\Parser\Value;
use GrahamCampbell\ResultType\Result;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    public function testParserInstanceOf()
    {
        $this->assertInstanceOf(ParserInterface::class, new Parser());
    }

    public function testFullParse()
    {
        $result = (new Parser())->parse("FOO=BAR\nFOO\nFOO=\"BAR  \n\"\nFOO=\"\\n\"");

        $this->assertIsArray($result);
        $this->assertCount(4, $result);

        $this->checkPositiveEntry($result[0], 'FOO', 'BAR');
        $this->checkEmptyEntry($result[1], 'FOO');
        $this->checkPositiveEntry($result[2], 'FOO', "BAR  \n");
        $this->checkPositiveEntry($result[3], 'FOO', "\n");
    }

    public function testBadEscapeParse()
    {
        $this->expectException(InvalidFileException::class);
        $this->expectExceptionMessage('Failed to parse dotenv file. Encountered an unexpected escape sequence at ["\q"].');

        (new Parser())->parse('FOO="\q"');
    }

    public function testParseInvalidSpaces()
    {
        $this->expectException(InvalidFileException::class);
        $this->expectExceptionMessage('Failed to parse dotenv file. Encountered unexpected whitespace at [bar baz].');

        (new Parser())->parse("FOO=bar baz\n");
    }

    public function testParseStrayEquals()
    {
        $this->expectException(InvalidFileException::class);
        $this->expectExceptionMessage('Failed to parse dotenv file. Encountered an unexpected equals at [=].');

        (new Parser())->parse("=\n");
    }

    public function testParseInvalidName()
    {
        $this->expectException(InvalidFileException::class);
        $this->expectExceptionMessage('Failed to parse dotenv file. Encountered an invalid name at [FOO_ASD!].');

        (new Parser())->parse('FOO_ASD!=BAZ');
    }

    private function checkPositiveEntry(Entry $entry, string $name, string $chars, array $vars = [])
    {
        $this->assertInstanceOf(Entry::class, $entry);
        $this->assertSame($name, $entry->getName());
        $this->assertTrue($entry->getValue()->isDefined());

        $value = $entry->getValue()->get();
        $this->assertInstanceOf(Value::class, $value);
        $this->assertSame($chars, $value->getChars());
        $this->assertSame($vars, $value->getVars());
    }

    private function checkEmptyEntry(Entry $entry, string $name)
    {
        $this->assertInstanceOf(Entry::class, $entry);
        $this->assertSame('FOO', $entry->getName());
        $this->assertFalse($entry->getValue()->isDefined());
    }
}
