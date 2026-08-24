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

    public function testNullParseOtherName()
    {
        $result = EntryParser::parse('BAR');
        $this->checkEmptyResult($result, 'BAR');
    }

    public function testUnicodeNameParse()
    {
        $result = EntryParser::parse('FOOƱ=BAZ');
        $this->checkPositiveResult($result, 'FOOƱ', 'BAZ');
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

    public function testInlineVariableOffsetAtChunkBoundary()
    {
        $prefix = \str_repeat('a', 999).'€';
        $result = EntryParser::parse('FOO="'.$prefix.'$BAR"');
        $this->checkPositiveResult($result, 'FOO', $prefix.'$BAR', [1000]);
    }

    public function testNonInlineVariable()
    {
        $result = EntryParser::parse('FOO=\'TEST $BAR $$BAZ\'');
        $this->checkPositiveResult($result, 'FOO', 'TEST $BAR $$BAZ');
        self::assertTrue($result->success()->isDefined());
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

    public function testExportParseTab()
    {
        $result = EntryParser::parse("export\t\"FOO\"='bar baz'");
        $this->checkPositiveResult($result, 'FOO', 'bar baz');
    }

    public function testExportParseSingleCharName()
    {
        $result = EntryParser::parse('export A=1');
        $this->checkPositiveResult($result, 'A', '1');
    }

    public function testExportParseNoWhitespace()
    {
        $result = EntryParser::parse('exportFOO=bar');
        $this->checkPositiveResult($result, 'exportFOO', 'bar');
    }

    public function testExportParseFail()
    {
        $result = EntryParser::parse('export "FOO="bar baz"');
        $this->checkErrorResult($result, 'Encountered an invalid name at ["FOO].');
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

    public function testParseInvalidUtf8Name()
    {
        $result = EntryParser::parse("\xC3=1");
        $this->checkErrorResult($result, "Encountered an invalid name at [\xC3].");
    }

    public function testParseTruncatedUtf8Name()
    {
        $result = EntryParser::parse("A\xE2\x82=1");
        $this->checkErrorResult($result, "Encountered an invalid name at [A\xE2\x82].");
    }

    public function testParseUtf16ByteOrderMarkName()
    {
        $result = EntryParser::parse("\xFF\xFE=1");
        $this->checkErrorResult($result, "Encountered an invalid name at [\xFF\xFE].");
    }

    public function testParseNameWithSubstituteCharacterConfigured()
    {
        if (\PHP_VERSION_ID < 80302 || !\extension_loaded('mbstring')) {
            self::markTestSkipped('Requires the native mbstring substitution behaviour of PHP 8.3.2.');
        }

        $previous = \mb_substitute_character();
        \mb_substitute_character(0x41);

        $quoted = EntryParser::parse("\"\xC3\"=evil");
        $exported = EntryParser::parse("export \xC3=evil");

        \mb_substitute_character($previous);

        $this->checkErrorResult($quoted, "Encountered an invalid name at [\xC3].");
        $this->checkErrorResult($exported, "Encountered an invalid name at [\xC3].");
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

    public function testParserMissingClosingSingleQuote()
    {
        $result = EntryParser::parse('TEST=\'erert');
        $this->checkErrorResult($result, 'Encountered a missing closing quote at [\'erert].');
    }

    public function testParserMissingClosingDoubleQuote()
    {
        $result = EntryParser::parse('TEST="erert');
        $this->checkErrorResult($result, 'Encountered a missing closing quote at ["erert].');
    }

    public function testParserMissingClosingQuotes()
    {
        $result = EntryParser::parse("TEST=\"erert\nTEST='erert\n");
        $this->checkErrorResult($result, 'Encountered a missing closing quote at ["erert].');
    }

    public function testParserClosingQuoteWithEscape()
    {
        $result = EntryParser::parse('TEST="\\');
        $this->checkErrorResult($result, 'Encountered a missing closing quote at ["\\].');
    }

    public function testParserErrorMessageIsCapped()
    {
        $result = EntryParser::parse('FOO="'.\str_repeat('a', 100).'\q"');
        $this->checkErrorResult($result, 'Encountered an unexpected escape sequence at ["'.\str_repeat('a', 79).'...].');
    }

    public function testParserErrorMessageCapDoesNotSplitCharacters()
    {
        $result = EntryParser::parse('FOO="'.\str_repeat('a', 77).'🚀'.\str_repeat('b', 20).'\q"');
        $this->checkErrorResult($result, 'Encountered an unexpected escape sequence at ["'.\str_repeat('a', 77).'...].');
    }

    public function testParserErrorMessageEscapesControlBytes()
    {
        $result = EntryParser::parse("FOO=\"a\x01b\q\"");
        $this->checkErrorResult($result, 'Encountered an unexpected escape sequence at ["a\001b\q"].');
    }

    /**
     * @param \GrahamCampbell\ResultType\Result<\Dotenv\Parser\Entry,string> $result
     * @param string                                                         $name
     * @param string                                                         $chars
     * @param int[]                                                          $vars
     *
     * @return void
     */
    private function checkPositiveResult(Result $result, string $name, string $chars, array $vars = [])
    {
        self::assertTrue($result->success()->isDefined());

        $entry = $result->success()->get();
        self::assertInstanceOf(Entry::class, $entry);
        self::assertSame($name, $entry->getName());
        self::assertTrue($entry->getValue()->isDefined());

        $value = $entry->getValue()->get();
        self::assertInstanceOf(Value::class, $value);
        self::assertSame($chars, $value->getChars());
        self::assertSame($vars, $value->getVars());
    }

    /**
     * @param \GrahamCampbell\ResultType\Result<\Dotenv\Parser\Entry,string> $result
     * @param string                                                         $name
     *
     * @return void
     */
    private function checkEmptyResult(Result $result, string $name)
    {
        self::assertTrue($result->success()->isDefined());

        $entry = $result->success()->get();
        self::assertInstanceOf(Entry::class, $entry);
        self::assertSame($name, $entry->getName());
        self::assertFalse($entry->getValue()->isDefined());
    }

    /**
     * @param \GrahamCampbell\ResultType\Result<\Dotenv\Parser\Entry,string> $result
     * @param string                                                         $error
     *
     * @return void
     */
    private function checkErrorResult(Result $result, string $error)
    {
        self::assertTrue($result->error()->isDefined());
        self::assertSame($error, $result->error()->get());
    }
}
