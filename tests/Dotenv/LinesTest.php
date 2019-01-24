<?php

use Dotenv\Lines;
use PHPUnit\Framework\TestCase;

class LinesTest extends TestCase
{
    public function testProcess()
    {
        $content = file_get_contents(dirname(__DIR__).'/fixtures/env/assertions.env');

        $expected = [
            'ASSERTVAR1=val1',
            'ASSERTVAR2=""',
            'ASSERTVAR3="val3   "',
            'ASSERTVAR4="0" # empty looking value',
            'ASSERTVAR5=#foo',
            "ASSERTVAR6=\"val1\nval2\"",
            "ASSERTVAR7=\"\nval3\" #",
            "ASSERTVAR8=\"val3\n\"",
            "ASSERTVAR9=\"\n\"",
        ];

        $this->assertSame($expected, Lines::process(preg_split("/(\r\n|\n|\r)/", $content)));
    }
}
