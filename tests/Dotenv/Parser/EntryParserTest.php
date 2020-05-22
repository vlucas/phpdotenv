<?php

declare(strict_types=1);

namespace Dotenv\Tests\Parser;

use Dotenv\Parser\Entry;
use Dotenv\Parser\EntryParser;
use Dotenv\Parser\Value;
use GrahamCampbell\ResultType\Result;
use PHPUnit\Framework\TestCase;

final class EntryParserTest extends TestCase
{
    public function testBasicParse()
    {
        $result = EntryParser::parse('FOO=BAR');
        $this->checkPositiveResult($result, 'FOO', 'BAR');
    }

    public function testNullParse()
    {
        $result = EntryParser::parse('FOO');
        $this->checkEmptyResult($result, 'FOO');
    }

    public function testQuotesParse()
    {
        $result = EntryParser::parse("FOO=\"BAR  \n\"");
        $this->checkPositiveResult($result, 'FOO', "BAR  \n");
    }

    public function testNewlineParse()
    {
        $result = EntryParser::parse('FOO="\n"');
        $this->checkPositiveResult($result, 'FOO', "\n");
    }

    public function testTabParseDouble()
    {
        $result = EntryParser::parse('FOO="\t"');
        $this->checkPositiveResult($result, 'FOO', "\t");
    }

    public function testTabParseSingle()
    {
        $result = EntryParser::parse('FOO=\'\t\'');
        $this->checkPositiveResult($result, 'FOO', '\t');
    }

    public function testNonEscapeParse1()
    {
        $result = EntryParser::parse('FOO=\n\v');
        $this->checkPositiveResult($result, 'FOO', '\n\v');
    }

    public function testNonEscapeParse2()
    {
        $result = EntryParser::parse('FOO=\q');
        $this->checkPositiveResult($result, 'FOO', '\q');
    }

    public function testBadEscapeParse()
    {
        $result = EntryParser::parse('FOO="\q"');
        $this->checkErrorResult($result, 'Encountered an unexpected escape sequence at ["\q"].');
    }

    public function testInlineVariable()
    {
        $result = EntryParser::parse('FOO=$BAR');
        $this->checkPositiveResult($result, 'FOO', '$BAR', [0]);
    }

    public function testInlineVariableOffset()
    {
        $result = EntryParser::parse('FOO=AAA$BAR');
        $this->checkPositiveResult($result, 'FOO', 'AAA$BAR', [3]);
    }

    public function testInlineVariables()
    {
        $result = EntryParser::parse('FOO="TEST $BAR $$BAZ"');
        $this->checkPositiveResult($result, 'FOO', 'TEST $BAR $$BAZ', [11, 10, 5]);
    }

    public function testNonInlineVariable()
    {
        $result = EntryParser::parse('FOO=\'TEST $BAR $$BAZ\'');
        $this->checkPositiveResult($result, 'FOO', 'TEST $BAR $$BAZ');
        $this->assertTrue($result->success()->isDefined());
    }

    public function testWhitespaceParse()
    {
        $result = EntryParser::parse("FOO=\"\n\"");
        $this->checkPositiveResult($result, 'FOO', "\n");
    }

    public function testExportParse()
    {
        $result = EntryParser::parse('export FOO="bar baz"');
        $this->checkPositiveResult($result, 'FOO', 'bar baz');
    }

    public function testClosingSlashParse()
    {
        $result = EntryParser::parse('SPVAR5="test some escaped characters like a quote \\" or maybe a backslash \\\\" # not escaped');
        $this->checkPositiveResult($result, 'SPVAR5', 'test some escaped characters like a quote " or maybe a backslash \\');
    }

    public function testParseInvalidSpaces()
    {
        $result = EntryParser::parse('FOO=bar baz');
        $this->checkErrorResult($result, 'Encountered unexpected whitespace at [bar baz].');
    }

    public function testParseStrayEquals()
    {
        $result = EntryParser::parse('=');
        $this->checkErrorResult($result, 'Encountered an unexpected equals at [=].');
    }

    public function testParseInvalidName()
    {
        $result = EntryParser::parse('FOO_ASD!=BAZ');
        $this->checkErrorResult($result, 'Encountered an invalid name at [FOO_ASD!].');
    }

    public function testParseUnicodeName()
    {
        $result = EntryParser::parse('FOOƱ=BAZ');
        $this->checkErrorResult($result, 'Encountered an invalid name at [FOOƱ].');
    }

    public function testParserEscapingDouble()
    {
        $result = EntryParser::parse('FOO_BAD="iiiiviiiixiiiiviiii\\a"');
        $this->checkErrorResult($result, 'Encountered an unexpected escape sequence at ["iiiiviiiixiiiiviiii\a"].');
    }

    public function testParserEscapingSingle()
    {
        $result = EntryParser::parse('FOO_BAD=\'iiiiviiiixiiiiviiii\\a\'');
        $this->checkPositiveResult($result, 'FOO_BAD', 'iiiiviiiixiiiiviiii\\a');
    }

    private function checkPositiveResult(Result $result, string $name, string $chars, array $vars = [])
    {
        $this->assertTrue($result->success()->isDefined());

        $entry = $result->success()->get();
        $this->assertInstanceOf(Entry::class, $entry);
        $this->assertSame($name, $entry->getName());
        $this->assertTrue($entry->getValue()->isDefined());

        $value = $entry->getValue()->get();
        $this->assertInstanceOf(Value::class, $value);
        $this->assertSame($chars, $value->getChars());
        $this->assertSame($vars, $value->getVars());
    }

    private function checkEmptyResult(Result $result, string $name)
    {
        $this->assertTrue($result->success()->isDefined());

        $entry = $result->success()->get();
        $this->assertInstanceOf(Entry::class, $entry);
        $this->assertSame('FOO', $entry->getName());
        $this->assertFalse($entry->getValue()->isDefined());
    }

    private function checkErrorResult(Result $result, string $error)
    {
        $this->assertTrue($result->error()->isDefined());
        $this->assertSame($error, $result->error()->get());
    }
}
