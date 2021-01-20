<?php

namespace Dotenv\Tests;

use Dotenv\Loader\Lines;
use PHPUnit\Framework\TestCase;

class LinesTest extends TestCase
{
    public function testProcessBasic()
    {
        $content = file_get_contents(dirname(__DIR__).'/fixtures/env/assertions.env');

        $expected = [
            'ASSERTVAR1=val1',
            'ASSERTVAR2=""',
            'ASSERTVAR3="val3   "',
            'ASSERTVAR4="0" # empty looking value',
            'ASSERTVAR5="#foo"',
            "ASSERTVAR6=\"val1\nval2\"",
            "ASSERTVAR7=\"\nval3\" #",
            "ASSERTVAR8=\"val3\n\"",
            "ASSERTVAR9=\"\n\n\"",
        ];

        self::assertSame($expected, Lines::process(preg_split("/(\r\n|\n|\r)/", $content)));
    }

    public function testProcessQuotes()
    {
        $content = file_get_contents(dirname(__DIR__).'/fixtures/env/multiline.env');

        $expected = [
            "TEST=\"test\n     test\\\"test\\\"\n     test\"",
            'TEST_ND="test\\ntest"',
            'TEST_NS=\'test\\ntest\'',
            'TEST_EQD="https://vision.googleapis.com/v1/images:annotate?key="',
            'TEST_EQS=\'https://vision.googleapis.com/v1/images:annotate?key=\'',
        ];

        self::assertSame($expected, Lines::process(preg_split("/(\r\n|\n|\r)/", $content)));
    }

    public function testProcessClosingSlash()
    {
        $lines = [
            'SPVAR5="test some escaped characters like a quote \" or maybe a backslash \\" # not escaped',
        ];

        $expected = [
            'SPVAR5="test some escaped characters like a quote \" or maybe a backslash \\" # not escaped',
        ];

        self::assertSame($expected, $lines);
    }

    public function testProcessBadQuotes()
    {
        $lines = [
            "TEST=\"erert\nTEST='erert\n",
        ];

        $expected = [
            "TEST=\"erert\nTEST='erert\n",
        ];

        self::assertSame($expected, $lines);
    }
}
