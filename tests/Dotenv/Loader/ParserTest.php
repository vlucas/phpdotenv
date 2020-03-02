<?php

declare(strict_types=1);

namespace Dotenv\Tests\Loader;

use Dotenv\Loader\Parser;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    public function testBasicParse()
    {
        $result = Parser::parse('FOO=BAR');
        $this->assertTrue($result->success()->isDefined());

        $output = $result->success()->get();
        $this->assertIsArray($output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO', $output[0]);
        $this->assertSame('BAR', $output[1]->getChars());
        $this->assertSame([], $output[1]->getVars());
    }

    public function testNullParse()
    {
        $result = Parser::parse('FOO');
        $this->assertTrue($result->success()->isDefined());

        $output = $result->success()->get();
        $this->assertIsArray($output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO', $output[0]);
        $this->assertNull($output[1]);
    }

    public function testQuotesParse()
    {
        $result = Parser::parse("FOO=\"BAR  \n\"");
        $this->assertTrue($result->success()->isDefined());

        $output = $result->success()->get();
        $this->assertIsArray($output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO', $output[0]);
        $this->assertSame("BAR  \n", $output[1]->getChars());
        $this->assertSame([], $output[1]->getVars());
    }

    public function testNewlineParse()
    {
        $result = Parser::parse('FOO="\n"');
        $this->assertTrue($result->success()->isDefined());

        $output = $result->success()->get();
        $this->assertIsArray($output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO', $output[0]);
        $this->assertSame("\n", $output[1]->getChars());
        $this->assertSame([], $output[1]->getVars());
    }

    public function testTabParseDouble()
    {
        $result = Parser::parse('FOO="\t"');
        $this->assertTrue($result->success()->isDefined());

        $output = $result->success()->get();
        $this->assertIsArray($output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO', $output[0]);
        $this->assertSame("\t", $output[1]->getChars());
        $this->assertSame([], $output[1]->getVars());
    }

    public function testTabParseSingle()
    {
        $result = Parser::parse('FOO=\'\t\'');
        $this->assertTrue($result->success()->isDefined());

        $output = $result->success()->get();
        $this->assertIsArray($output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO', $output[0]);
        $this->assertSame('\t', $output[1]->getChars());
        $this->assertSame([], $output[1]->getVars());
    }

    public function testNonEscapeParse1()
    {
        $result = Parser::parse('FOO=\n\v');
        $this->assertTrue($result->success()->isDefined());

        $output = $result->success()->get();
        $this->assertIsArray($output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO', $output[0]);
        $this->assertSame('\n\v', $output[1]->getChars());
        $this->assertSame([], $output[1]->getVars());
    }

    public function testNonEscapeParse2()
    {
        $result = Parser::parse('FOO=\q');
        $this->assertTrue($result->success()->isDefined());

        $output = $result->success()->get();
        $this->assertIsArray($output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO', $output[0]);
        $this->assertSame('\q', $output[1]->getChars());
        $this->assertSame([], $output[1]->getVars());
    }

    public function testBadEscapeParse()
    {
        $result = Parser::parse('FOO="\q"');
        $this->assertTrue($result->error()->isDefined());
        $this->assertSame('Failed to parse dotenv file due to an unexpected escape sequence. Failed at ["\q"].', $result->error()->get());
    }

    public function testInlineVariable()
    {
        $result = Parser::parse('FOO=$BAR');
        $this->assertTrue($result->success()->isDefined());

        $output = $result->success()->get();
        $this->assertIsArray($output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO', $output[0]);
        $this->assertSame('$BAR', $output[1]->getChars());
        $this->assertSame([0], $output[1]->getVars());
    }

    public function testInlineVariableOffset()
    {
        $result = Parser::parse('FOO=AAA$BAR');
        $this->assertTrue($result->success()->isDefined());

        $output = $result->success()->get();
        $this->assertIsArray($output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO', $output[0]);
        $this->assertSame('AAA$BAR', $output[1]->getChars());
        $this->assertSame([3], $output[1]->getVars());
    }

    public function testInlineVariables()
    {
        $result = Parser::parse('FOO="TEST $BAR $$BAZ"');
        $this->assertTrue($result->success()->isDefined());

        $output = $result->success()->get();
        $this->assertIsArray($output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO', $output[0]);
        $this->assertSame('TEST $BAR $$BAZ', $output[1]->getChars());
        $this->assertSame([11, 10, 5], $output[1]->getVars());
    }

    public function testNonInlineVariable()
    {
        $result = Parser::parse('FOO=\'TEST $BAR $$BAZ\'');
        $this->assertTrue($result->success()->isDefined());

        $output = $result->success()->get();
        $this->assertIsArray($output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO', $output[0]);
        $this->assertSame('TEST $BAR $$BAZ', $output[1]->getChars());
        $this->assertSame([], $output[1]->getVars());
    }

    public function testWhitespaceParse()
    {
        $result = Parser::parse("FOO=\"\n\"");
        $this->assertTrue($result->success()->isDefined());

        $output = $result->success()->get();
        $this->assertIsArray($output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO', $output[0]);
        $this->assertSame("\n", $output[1]->getChars());
        $this->assertSame([], $output[1]->getVars());
    }

    public function testExportParse()
    {
        $result = Parser::parse('export FOO="bar baz"');
        $this->assertTrue($result->success()->isDefined());

        $output = $result->success()->get();
        $this->assertIsArray($output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO', $output[0]);
        $this->assertSame('bar baz', $output[1]->getChars());
        $this->assertSame([], $output[1]->getVars());
    }

    public function testClosingSlashParse()
    {
        $result = Parser::parse('SPVAR5="test some escaped characters like a quote \\" or maybe a backslash \\\\" # not escaped');
        $this->assertTrue($result->success()->isDefined());

        $output = $result->success()->get();
        $this->assertIsArray($output);
        $this->assertCount(2, $output);
        $this->assertSame('SPVAR5', $output[0]);
        $this->assertSame('test some escaped characters like a quote " or maybe a backslash \\', $output[1]->getChars());
        $this->assertSame([], $output[1]->getVars());
    }

    public function testParseInvalidSpaces()
    {
        $result = Parser::parse('FOO=bar baz');
        $this->assertTrue($result->error()->isDefined());
        $this->assertSame('Failed to parse dotenv file due to unexpected whitespace. Failed at [bar baz].', $result->error()->get());
    }

    public function testParseStrayEquals()
    {
        $result = Parser::parse('=');
        $this->assertTrue($result->error()->isDefined());
        $this->assertSame('Failed to parse dotenv file due to an unexpected equals. Failed at [=].', $result->error()->get());
    }

    public function testParseInvalidName()
    {
        $result = Parser::parse('FOO_ASD!=BAZ');
        $this->assertTrue($result->error()->isDefined());
        $this->assertSame('Failed to parse dotenv file due to an invalid name. Failed at [FOO_ASD!].', $result->error()->get());
    }

    public function testParserEscapingDouble()
    {
        $result = Parser::parse('FOO_BAD="iiiiviiiixiiiiviiii\\a"');
        $this->assertTrue($result->error()->isDefined());
        $this->assertSame('Failed to parse dotenv file due to an unexpected escape sequence. Failed at ["iiiiviiiixiiiiviiii\a"].', $result->error()->get());
    }

    public function testParserEscapingSingle()
    {
        $result = Parser::parse('FOO_BAD=\'iiiiviiiixiiiiviiii\\a\'');
        $this->assertTrue($result->success()->isDefined());

        $output = $result->success()->get();
        $this->assertIsArray($output);
        $this->assertCount(2, $output);
        $this->assertSame('FOO_BAD', $output[0]);
        $this->assertSame('iiiiviiiixiiiiviiii\\a', $output[1]->getChars());
        $this->assertSame([], $output[1]->getVars());
    }
}
