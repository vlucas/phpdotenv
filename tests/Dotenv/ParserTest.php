<?php

use Dotenv\Parser;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    public function testBasicParse()
    {
        $output = Parser::parse('FOO=BAR');

        $this->assertInternalType('array', $output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO', $output[0]);
        $this->assertSame('BAR', $output[1]->getChars());
        $this->assertSame([], $output[1]->getVars());
    }

    public function testQuotesParse()
    {
        $output = Parser::parse("FOO=\"BAR  \n\"");

        $this->assertInternalType('array', $output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO', $output[0]);
        $this->assertSame("BAR  \n", $output[1]->getChars());
        $this->assertSame([], $output[1]->getVars());
    }

    public function testNewlineParse()
    {
        $output = Parser::parse('FOO="\n"');

        $this->assertInternalType('array', $output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO', $output[0]);
        $this->assertSame("\n", $output[1]->getChars());
        $this->assertSame([], $output[1]->getVars());
    }

    public function testTabParseDouble()
    {
        $output = Parser::parse('FOO="\t"');

        $this->assertInternalType('array', $output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO', $output[0]);
        $this->assertSame("\t", $output[1]->getChars());
        $this->assertSame([], $output[1]->getVars());
    }

    public function testTabParseSingle()
    {
        $output = Parser::parse('FOO=\'\t\'');

        $this->assertInternalType('array', $output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO', $output[0]);
        $this->assertSame('\t', $output[1]->getChars());
        $this->assertSame([], $output[1]->getVars());
    }

    public function testNonEscapeParse1()
    {
        $output = Parser::parse('FOO=\n\v');

        $this->assertInternalType('array', $output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO', $output[0]);
        $this->assertSame('\n\v', $output[1]->getChars());
        $this->assertSame([], $output[1]->getVars());
    }

    public function testNonEscapeParse2()
    {
        $output = Parser::parse('FOO=\q');

        $this->assertInternalType('array', $output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO', $output[0]);
        $this->assertSame('\q', $output[1]->getChars());
        $this->assertSame([], $output[1]->getVars());
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

        $this->assertInternalType('array', $output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO', $output[0]);
        $this->assertSame('$BAR', $output[1]->getChars());
        $this->assertSame([0], $output[1]->getVars());
    }

    public function testInlineVariables()
    {
        $output = Parser::parse('FOO="TEST $BAR $$BAZ"');

        $this->assertInternalType('array', $output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO', $output[0]);
        $this->assertSame('TEST $BAR $$BAZ', $output[1]->getChars());
        $this->assertSame([11, 10, 5], $output[1]->getVars());
    }

    public function testNonInlineVariable()
    {
        $output = Parser::parse('FOO=\'TEST $BAR $$BAZ\'');

        $this->assertInternalType('array', $output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO', $output[0]);
        $this->assertSame('TEST $BAR $$BAZ', $output[1]->getChars());
        $this->assertSame([], $output[1]->getVars());
    }

    public function testWhitespaceParse()
    {
        $output = Parser::parse("FOO=\"\n\"");

        $this->assertInternalType('array', $output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO', $output[0]);
        $this->assertSame("\n", $output[1]->getChars());
        $this->assertSame([], $output[1]->getVars());
    }

    public function testExportParse()
    {
        $output = Parser::parse('export FOO="bar baz"');

        $this->assertInternalType('array', $output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO', $output[0]);
        $this->assertSame('bar baz', $output[1]->getChars());
        $this->assertSame([], $output[1]->getVars());
    }

    public function testClosingSlashParse()
    {
        $output = Parser::parse('SPVAR5="test some escaped characters like a quote \\" or maybe a backslash \\\\" # not escaped');

        $this->assertInternalType('array', $output);
        $this->assertCount(2, $output);
        $this->assertSame('SPVAR5', $output[0]);
        $this->assertSame('test some escaped characters like a quote " or maybe a backslash \\', $output[1]->getChars());
        $this->assertSame([], $output[1]->getVars());
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

        $this->assertInternalType('array', $output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO_BAD', $output[0]);
        $this->assertSame('iiiiviiiixiiiiviiii\\a', $output[1]->getChars());
        $this->assertSame([], $output[1]->getVars());
    }
}
