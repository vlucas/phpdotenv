<?php

use Dotenv\Lines;
use PHPUnit\Framework\TestCase;

class LinesTest extends TestCase
{
    /**
     * @var string|false
     */
    protected $autodetect;

    public function setUp()
    {
        $this->autodetect = ini_get('auto_detect_line_endings');
        ini_set('auto_detect_line_endings', '1');
    }

    public function tearDown()
    {
        ini_set('auto_detect_line_endings', $this->autodetect);
    }

    public function testProcess()
    {
        $content = file(
            dirname(__DIR__).'/fixtures/env/assertions.env',
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
        );

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

        $this->assertSame($expected, Lines::process($content));
    }
}
