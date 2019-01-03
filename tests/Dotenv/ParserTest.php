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

    /**
     * @expectedException \Dotenv\Exception\InvalidFileException
     * @expectedExceptionMessage Failed to parse dotenv file due to an unexpected space. Failed at [bar baz].
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
}
