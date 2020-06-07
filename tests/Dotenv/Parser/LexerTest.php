<?php

declare(strict_types=1);

namespace Dotenv\Tests\Parser;

use Dotenv\Parser\Lexer;
use PHPUnit\Framework\TestCase;

final class LexerTest extends TestCase
{
    /**
     * @return array{string,string[]}[]
     */
    public function provideLexCases()
    {
        return [
            ['', []],
            ['FOO', ['FOO']],
            ['FOO bar', ['FOO', ' ', 'bar']],
            ['FOO\\n()ab', ['FOO', '\\', 'n()ab']],
            ["FOO\n\n   A", ['FOO', "\n\n", '   ', 'A']],
            ['"VAL"', ['"', 'VAL', '"']],
            ['\' \'', ['\'', ' ', '\'']],
        ];
    }

    /**
     * @dataProvider provideLexCases
     *
     * @param string   $input
     * @param string[] $output
     *
     * @return void
     */
    public function testLex(string $input, array $output)
    {
        self::assertSame($output, Lexer::lex($input));
    }
}
