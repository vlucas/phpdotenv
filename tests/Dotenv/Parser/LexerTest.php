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
    public static function provideLexCases()
    {
        return [
            ['', []],
            ['FOO', ['FOO']],
            ['FOO bar', ['FOO', ' ', 'bar']],
            ['FOO\\n()ab', ['FOO', '\\', 'n()ab']],
            ["FOO\n\n   A", ['FOO', "\n\n", '   ', 'A']],
            ['"VA=L"', ['"', 'VA=L', '"']],
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
        self::assertSame($output, \iterator_to_array(Lexer::lex($input)));
    }
}
