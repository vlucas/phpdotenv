<?php

use Dotenv\Parser;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    public function testBasicParse()
    {
        $this->assertSame(['FOO', 'BAR'], Parser::parse('FOO=BAR'));
    }

    public function testQuotesParse()
    {
        $this->assertSame(['FOO', "BAR  \n"], Parser::parse("FOO=\"BAR  \n\""));
    }

    public function testExportParse()
    {
        $this->assertSame(['FOO', 'bar baz'], Parser::parse('export FOO="bar baz"'));
    }
}
