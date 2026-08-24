<?php

declare(strict_types=1);

namespace Dotenv\Tests\Parser;

use Dotenv\Exception\InvalidFileException;
use Dotenv\Parser\Entry;
use Dotenv\Parser\Parser;
use Dotenv\Parser\ParserInterface;
use Dotenv\Parser\Value;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    public function testParserInstanceOf()
    {
        self::assertInstanceOf(ParserInterface::class, new Parser());
    }

    public function testFullParse()
    {
        $result = (new Parser())->parse("FOO=BAR\nFOO\nFOO=\"BAR  \n\"\nFOO=\"\\n\"\nBAR");

        self::assertIsArray($result);
        self::assertCount(5, $result);

        $this->checkPositiveEntry($result[0], 'FOO', 'BAR');
        $this->checkEmptyEntry($result[1], 'FOO');
        $this->checkPositiveEntry($result[2], 'FOO', "BAR  \n");
        $this->checkPositiveEntry($result[3], 'FOO', "\n");
        $this->checkEmptyEntry($result[4], 'BAR');
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

    public function testParseInvalidUtf8Name()
    {
        $this->expectException(InvalidFileException::class);
        $this->expectExceptionMessage("Failed to parse dotenv file. Encountered an invalid name at [\xC3].");

        (new Parser())->parse("\xC3=1");
    }

    /**
     * @param \Dotenv\Parser\Entry $entry
     * @param string               $name
     * @param string               $chars
     * @param int[]                $vars
     *
     * @return void
     */
    private function checkPositiveEntry(Entry $entry, string $name, string $chars, array $vars = [])
    {
        self::assertInstanceOf(Entry::class, $entry);
        self::assertSame($name, $entry->getName());
        self::assertTrue($entry->getValue()->isDefined());

        $value = $entry->getValue()->get();
        self::assertInstanceOf(Value::class, $value);
        self::assertSame($chars, $value->getChars());
        self::assertSame($vars, $value->getVars());
    }

    /**
     * @param \Dotenv\Parser\Entry $entry
     * @param string               $name
     *
     * @return void
     */
    private function checkEmptyEntry(Entry $entry, string $name)
    {
        self::assertInstanceOf(Entry::class, $entry);
        self::assertSame($name, $entry->getName());
        self::assertFalse($entry->getValue()->isDefined());
    }
}
