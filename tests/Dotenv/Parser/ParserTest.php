<?php

declare(strict_types=1);

namespace Dotenv\Tests\Parser;

use Dotenv\Parser\Entry;
use Dotenv\Parser\Parser;
use Dotenv\Parser\ParserInterface;
use Dotenv\Parser\Value;
use Dotenv\Result\Result;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    public function testParserInstanceOf()
    {
        $this->assertInstanceOf(ParserInterface::class, new Parser());
    }

    public function testBasicParse()
    {
        $result = (new Parser())->parse('FOO=BAR');
        $this->checkPositiveResult($result, 'FOO', 'BAR');
    }

    public function testNullParse()
    {
        $result = (new Parser())->parse('FOO');
        $this->checkEmptyResult($result, 'FOO');
    }

    public function testQuotesParse()
    {
        $result = (new Parser())->parse("FOO=\"BAR  \n\"");
        $this->checkPositiveResult($result, 'FOO', "BAR  \n");
    }

    public function testNewlineParse()
    {
        $result = (new Parser())->parse('FOO="\n"');
        $this->checkPositiveResult($result, 'FOO', "\n");
    }

    public function testTabParseDouble()
    {
        $result = (new Parser())->parse('FOO="\t"');
        $this->checkPositiveResult($result, 'FOO', "\t");
    }

    public function testTabParseSingle()
    {
        $result = (new Parser())->parse('FOO=\'\t\'');
        $this->checkPositiveResult($result, 'FOO', '\t');
    }

    public function testNonEscapeParse1()
    {
        $result = (new Parser())->parse('FOO=\n\v');
        $this->checkPositiveResult($result, 'FOO', '\n\v');
    }

    public function testNonEscapeParse2()
    {
        $result = (new Parser())->parse('FOO=\q');
        $this->checkPositiveResult($result, 'FOO', '\q');
    }

    public function testBadEscapeParse()
    {
        $result = (new Parser())->parse('FOO="\q"');
        $this->checkErrorResult($result, 'Failed to parse dotenv file due to an unexpected escape sequence. Failed at ["\q"].');
    }

    public function testInlineVariable()
    {
        $result = (new Parser())->parse('FOO=$BAR');
        $this->checkPositiveResult($result, 'FOO', '$BAR', [0]);
    }

    public function testInlineVariableOffset()
    {
        $result = (new Parser())->parse('FOO=AAA$BAR');
        $this->checkPositiveResult($result, 'FOO', 'AAA$BAR', [3]);
    }

    public function testInlineVariables()
    {
        $result = (new Parser())->parse('FOO="TEST $BAR $$BAZ"');
        $this->checkPositiveResult($result, 'FOO', 'TEST $BAR $$BAZ', [11, 10, 5]);
    }

    public function testNonInlineVariable()
    {
        $result = (new Parser())->parse('FOO=\'TEST $BAR $$BAZ\'');
        $this->checkPositiveResult($result, 'FOO', 'TEST $BAR $$BAZ');
        $this->assertTrue($result->success()->isDefined());
    }

    public function testWhitespaceParse()
    {
        $result = (new Parser())->parse("FOO=\"\n\"");
        $this->checkPositiveResult($result, 'FOO', "\n");
    }

    public function testExportParse()
    {
        $result = (new Parser())->parse('export FOO="bar baz"');
        $this->checkPositiveResult($result, 'FOO', 'bar baz');
    }

    public function testClosingSlashParse()
    {
        $result = (new Parser())->parse('SPVAR5="test some escaped characters like a quote \\" or maybe a backslash \\\\" # not escaped');
        $this->checkPositiveResult($result, 'SPVAR5', 'test some escaped characters like a quote " or maybe a backslash \\');
    }

    public function testParseInvalidSpaces()
    {
        $result = (new Parser())->parse('FOO=bar baz');
        $this->checkErrorResult($result, 'Failed to parse dotenv file due to unexpected whitespace. Failed at [bar baz].');
    }

    public function testParseStrayEquals()
    {
        $result = (new Parser())->parse('=');
        $this->checkErrorResult($result, 'Failed to parse dotenv file due to an unexpected equals. Failed at [=].');
    }

    public function testParseInvalidName()
    {
        $result = (new Parser())->parse('FOO_ASD!=BAZ');
        $this->checkErrorResult($result, 'Failed to parse dotenv file due to an invalid name. Failed at [FOO_ASD!].');
    }

    public function testParserEscapingDouble()
    {
        $result = (new Parser())->parse('FOO_BAD="iiiiviiiixiiiiviiii\\a"');
        $this->checkErrorResult($result, 'Failed to parse dotenv file due to an unexpected escape sequence. Failed at ["iiiiviiiixiiiiviiii\a"].');
    }

    public function testParserEscapingSingle()
    {
        $result = (new Parser())->parse('FOO_BAD=\'iiiiviiiixiiiiviiii\\a\'');
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
