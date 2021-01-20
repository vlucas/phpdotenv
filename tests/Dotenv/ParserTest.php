<?php

namespace Dotenv\Tests;

use Dotenv\Loader\Parser;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    public function testBasicParse()
    {
        $output = Parser::parse('FOO=BAR');

        self::assertInternalType('array', $output);
        self::assertCount(2, $output);
        self::assertSame('FOO', $output[0]);
        self::assertSame('BAR', $output[1]->getChars());
        self::assertSame([], $output[1]->getVars());
    }

    public function testNullParse()
    {
        $output = Parser::parse('FOO');

        self::assertInternalType('array', $output);
        self::assertCount(2, $output);
        self::assertSame('FOO', $output[0]);
        self::assertNull($output[1]);
    }

    public function testQuotesParse()
    {
        $output = Parser::parse("FOO=\"BAR  \n\"");

        self::assertInternalType('array', $output);
        self::assertCount(2, $output);
        self::assertSame('FOO', $output[0]);
        self::assertSame("BAR  \n", $output[1]->getChars());
        self::assertSame([], $output[1]->getVars());
    }

    public function testNewlineParse()
    {
        $output = Parser::parse('FOO="\n"');

        self::assertInternalType('array', $output);
        self::assertCount(2, $output);
        self::assertSame('FOO', $output[0]);
        self::assertSame("\n", $output[1]->getChars());
        self::assertSame([], $output[1]->getVars());
    }

    public function testTabParseDouble()
    {
        $output = Parser::parse('FOO="\t"');

        self::assertInternalType('array', $output);
        self::assertCount(2, $output);
        self::assertSame('FOO', $output[0]);
        self::assertSame("\t", $output[1]->getChars());
        self::assertSame([], $output[1]->getVars());
    }

    public function testTabParseSingle()
    {
        $output = Parser::parse('FOO=\'\t\'');

        self::assertInternalType('array', $output);
        self::assertCount(2, $output);
        self::assertSame('FOO', $output[0]);
        self::assertSame('\t', $output[1]->getChars());
        self::assertSame([], $output[1]->getVars());
    }

    public function testNonEscapeParse1()
    {
        $output = Parser::parse('FOO=\n\v');

        self::assertInternalType('array', $output);
        self::assertCount(2, $output);
        self::assertSame('FOO', $output[0]);
        self::assertSame('\n\v', $output[1]->getChars());
        self::assertSame([], $output[1]->getVars());
    }

    public function testNonEscapeParse2()
    {
        $output = Parser::parse('FOO=\q');

        self::assertInternalType('array', $output);
        self::assertCount(2, $output);
        self::assertSame('FOO', $output[0]);
        self::assertSame('\q', $output[1]->getChars());
        self::assertSame([], $output[1]->getVars());
    }

    /**
     * @expectedException \Dotenv\Exception\InvalidFileException
     * @expectedExceptionMessage Failed to parse dotenv file due to an unexpected escape sequence. Failed at ["\q"].
     */
    public function testBadEscapeParse()
    {
        Parser::parse('FOO="\q"');
    }

    public function testInlineVariable()
    {
        $output = Parser::parse('FOO=$BAR');

        self::assertInternalType('array', $output);
        self::assertCount(2, $output);
        self::assertSame('FOO', $output[0]);
        self::assertSame('$BAR', $output[1]->getChars());
        self::assertSame([0], $output[1]->getVars());
    }

    public function testInlineVariableOffset()
    {
        $output = Parser::parse('FOO=AAA$BAR');

        self::assertInternalType('array', $output);
        self::assertCount(2, $output);
        self::assertSame('FOO', $output[0]);
        self::assertSame('AAA$BAR', $output[1]->getChars());
        self::assertSame([3], $output[1]->getVars());
    }

    public function testInlineVariables()
    {
        $output = Parser::parse('FOO="TEST $BAR $$BAZ"');

        self::assertInternalType('array', $output);
        self::assertCount(2, $output);
        self::assertSame('FOO', $output[0]);
        self::assertSame('TEST $BAR $$BAZ', $output[1]->getChars());
        self::assertSame([11, 10, 5], $output[1]->getVars());
    }

    public function testNonInlineVariable()
    {
        $output = Parser::parse('FOO=\'TEST $BAR $$BAZ\'');

        self::assertInternalType('array', $output);
        self::assertCount(2, $output);
        self::assertSame('FOO', $output[0]);
        self::assertSame('TEST $BAR $$BAZ', $output[1]->getChars());
        self::assertSame([], $output[1]->getVars());
    }

    public function testWhitespaceParse()
    {
        $output = Parser::parse("FOO=\"\n\"");

        self::assertInternalType('array', $output);
        self::assertCount(2, $output);
        self::assertSame('FOO', $output[0]);
        self::assertSame("\n", $output[1]->getChars());
        self::assertSame([], $output[1]->getVars());
    }

    public function testExportParse()
    {
        $output = Parser::parse('export FOO="bar baz"');

        self::assertInternalType('array', $output);
        self::assertCount(2, $output);
        self::assertSame('FOO', $output[0]);
        self::assertSame('bar baz', $output[1]->getChars());
        self::assertSame([], $output[1]->getVars());
    }

    public function testClosingSlashParse()
    {
        $output = Parser::parse('SPVAR5="test some escaped characters like a quote \\" or maybe a backslash \\\\" # not escaped');

        self::assertInternalType('array', $output);
        self::assertCount(2, $output);
        self::assertSame('SPVAR5', $output[0]);
        self::assertSame('test some escaped characters like a quote " or maybe a backslash \\', $output[1]->getChars());
        self::assertSame([], $output[1]->getVars());
    }

    /**
     * @expectedException \Dotenv\Exception\InvalidFileException
     * @expectedExceptionMessage Failed to parse dotenv file due to unexpected whitespace. Failed at [bar baz].
     */
    public function testParseInvalidSpaces()
    {
        Parser::parse('FOO=bar baz');
    }

    /**
     * @expectedException \Dotenv\Exception\InvalidFileException
     * @expectedExceptionMessage Failed to parse dotenv file due to an unexpected equals. Failed at [=].
     */
    public function testParseStrayEquals()
    {
        Parser::parse('=');
    }

    /**
     * @expectedException \Dotenv\Exception\InvalidFileException
     * @expectedExceptionMessage Failed to parse dotenv file due to an invalid name. Failed at [FOO_ASD!].
     */
    public function testParseInvalidName()
    {
        Parser::parse('FOO_ASD!=BAZ');
    }

    /**
     * @expectedException \Dotenv\Exception\InvalidFileException
     * @expectedExceptionMessage Failed to parse dotenv file due to an unexpected escape sequence. Failed at ["iiiiviiiixiiiiviiii\a"].
     */
    public function testParserEscapingDouble()
    {
        Parser::parse('FOO_BAD="iiiiviiiixiiiiviiii\\a"');
    }

    public function testParserEscapingSingle()
    {
        $output = Parser::parse('FOO_BAD=\'iiiiviiiixiiiiviiii\\a\'');

        self::assertInternalType('array', $output);
        self::assertCount(2, $output);
        self::assertSame('FOO_BAD', $output[0]);
        self::assertSame('iiiiviiiixiiiiviiii\\a', $output[1]->getChars());
        self::assertSame([], $output[1]->getVars());
    }

    /**
     * @expectedException \Dotenv\Exception\InvalidFileException
     * @expectedExceptionMessage Failed to parse dotenv file due to a missing closing quote. Failed at ['erert].
     */
    public function testMissingClosingSingleQuote()
    {
        Parser::parse('TEST=\'erert');
    }

    /**
     * @expectedException \Dotenv\Exception\InvalidFileException
     * @expectedExceptionMessage Failed to parse dotenv file due to a missing closing quote. Failed at ["erert].
     */
    public function testMissingClosingDoubleQuote()
    {
        Parser::parse('TEST="erert');
    }

    /**
     * @expectedException \Dotenv\Exception\InvalidFileException
     * @expectedExceptionMessage Failed to parse dotenv file due to a missing closing quote. Failed at ["erert].
     */
    public function testMissingClosingQuotes()
    {
        Parser::parse("TEST=\"erert\nTEST='erert\n");
    }

    /**
     * @expectedException \Dotenv\Exception\InvalidFileException
     * @expectedExceptionMessage Failed to parse dotenv file due to a missing closing quote. Failed at ["\].
     */
    public function testMissingClosingQuoteWithEscape()
    {
        Parser::parse('TEST="\\');
    }
}
