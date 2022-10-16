<?php

declare(strict_types=1);

namespace Dotenv\Tests\Parser;

use Dotenv\Parser\Lines;
use Dotenv\Util\Regex;
use PHPUnit\Framework\TestCase;

final class LinesTest extends TestCase
{
    public function testProcessBasic()
    {
        $content = \file_get_contents(\dirname(\dirname(__DIR__)).'/fixtures/env/assertions.env');
        self::assertIsString($content);
        $result = Regex::split("/(\r\n|\n|\r)/", $content);
        self::assertTrue($result->success()->isDefined());

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

        self::assertSame($expected, Lines::process($result->success()->get()));
    }

    public function testProcessQuotes()
    {
        $content = \file_get_contents(\dirname(\dirname(__DIR__)).'/fixtures/env/multiline.env');
        self::assertIsString($content);
        $result = Regex::split("/(\r\n|\n|\r)/", $content);
        self::assertTrue($result->success()->isDefined());

        $expected = [
            "TEST=\"test\n     test\\\"test\\\"\n     test\"",
            'TEST_ND="test\\ntest"',
            'TEST_NS=\'test\\ntest\'',
            'TEST_EQD="https://vision.googleapis.com/v1/images:annotate?key="',
            'TEST_EQS=\'https://vision.googleapis.com/v1/images:annotate?key=\'',
            "BASE64_ENCODED_MULTILINE=\"qS1zCzMVVUJWQShokv6YVYi+ruKSC/bHV7GmEiyVkLaBWJHNVHCHsgTksEBsy8wJ\nuwycAvR07ZyOJJed4XTRMKnKp1/v+6UATpWzkIjZXytK+pD+XlZimUHTx3uiDcmU\njhQX1wWSxHDqrSWxeIJiTD+BuUyId8FzmXQ3TcBydJ474tmOU2F492ubk3LAiZ18\nmhiRGoshXAOSbS/P3+RZi4bDeNE/No4=\"",
        ];

        self::assertSame($expected, Lines::process($result->success()->get()));
    }
}
