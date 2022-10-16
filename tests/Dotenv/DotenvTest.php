<?php

declare(strict_types=1);

namespace Dotenv\Tests;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidEncodingException;
use Dotenv\Exception\InvalidPathException;
use Dotenv\Loader\Loader;
use Dotenv\Parser\Parser;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Store\StoreBuilder;
use PHPUnit\Framework\TestCase;

final class DotenvTest extends TestCase
{
    /**
     * @var string
     */
    private static $folder;

    /**
     * @beforeClass
     *
     * @return void
     */
    public static function setFolder()
    {
        self::$folder = \dirname(__DIR__).'/fixtures/env';
    }

    public function testDotenvThrowsExceptionIfUnableToLoadFile()
    {
        $dotenv = Dotenv::createMutable(__DIR__);

        $this->expectException(InvalidPathException::class);
        $this->expectExceptionMessage('Unable to read any of the environment file(s) at');

        $dotenv->load();
    }

    public function testDotenvThrowsExceptionIfUnableToLoadFiles()
    {
        $dotenv = Dotenv::createMutable([__DIR__, __DIR__.'/foo/bar']);

        $this->expectException(InvalidPathException::class);
        $this->expectExceptionMessage('Unable to read any of the environment file(s) at');

        $dotenv->load();
    }

    public function testDotenvThrowsExceptionWhenNoFiles()
    {
        $dotenv = Dotenv::createMutable([]);

        $this->expectException(InvalidPathException::class);
        $this->expectExceptionMessage('At least one environment file path must be provided.');

        $dotenv->load();
    }

    public function testDotenvTriesPathsToLoad()
    {
        $dotenv = Dotenv::createMutable([__DIR__, self::$folder]);
        self::assertCount(4, $dotenv->load());
    }

    public function testDotenvTriesPathsToLoadTwice()
    {
        $dotenv = Dotenv::createMutable([__DIR__, self::$folder]);
        self::assertCount(4, $dotenv->load());

        $dotenv = Dotenv::createImmutable([__DIR__, self::$folder]);
        self::assertCount(0, $dotenv->load());
    }

    public function testDotenvTriesPathsToSafeLoad()
    {
        $dotenv = Dotenv::createMutable([__DIR__, self::$folder]);
        self::assertCount(4, $dotenv->safeLoad());
    }

    public function testDotenvSkipsLoadingIfFileIsMissing()
    {
        $dotenv = Dotenv::createMutable(__DIR__);
        self::assertSame([], $dotenv->safeLoad());
    }

    public function testDotenvLoadsEnvironmentVars()
    {
        $dotenv = Dotenv::createMutable(self::$folder);
        self::assertSame(
            ['FOO' => 'bar', 'BAR' => 'baz', 'SPACED' => 'with spaces', 'NULL' => ''],
            $dotenv->load()
        );
        self::assertSame('bar', $_SERVER['FOO']);
        self::assertSame('baz', $_SERVER['BAR']);
        self::assertSame('with spaces', $_SERVER['SPACED']);
        self::assertEmpty($_SERVER['NULL']);
    }

    public function testDotenvLoadsEnvironmentVarsMultipleWithShortCircuitMode()
    {
        $dotenv = Dotenv::createMutable(self::$folder, ['.env', 'example.env']);

        self::assertSame(
            ['FOO' => 'bar', 'BAR' => 'baz', 'SPACED' => 'with spaces', 'NULL' => ''],
            $dotenv->load()
        );
    }

    public function testDotenvLoadsEnvironmentVarsMultipleWithoutShortCircuitMode()
    {
        $dotenv = Dotenv::createMutable(self::$folder, ['.env', 'example.env'], false);

        self::assertSame(
            ['FOO' => 'bar', 'BAR' => 'baz', 'SPACED' => 'with spaces', 'NULL' => '', 'EG' => 'example'],
            $dotenv->load()
        );
    }

    public function testCommentedDotenvLoadsEnvironmentVars()
    {
        $dotenv = Dotenv::createMutable(self::$folder, 'commented.env');
        $dotenv->load();
        self::assertSame('bar', $_SERVER['CFOO']);
        self::assertFalse(isset($_SERVER['CBAR']));
        self::assertFalse(isset($_SERVER['CZOO']));
        self::assertSame('with spaces', $_SERVER['CSPACED']);
        self::assertSame('a value with a # character', $_SERVER['CQUOTES']);
        self::assertSame('a value with a # character & a quote " character inside quotes', $_SERVER['CQUOTESWITHQUOTE']);
        self::assertEmpty($_SERVER['CNULL']);
        self::assertEmpty($_SERVER['EMPTY']);
        self::assertEmpty($_SERVER['EMPTY2']);
        self::assertSame('foo', $_SERVER['FOOO']);
    }

    public function testQuotedDotenvLoadsEnvironmentVars()
    {
        $dotenv = Dotenv::createMutable(self::$folder, 'quoted.env');
        $dotenv->load();
        self::assertSame('bar', $_SERVER['QFOO']);
        self::assertSame('baz', $_SERVER['QBAR']);
        self::assertSame('with spaces', $_SERVER['QSPACED']);
        self::assertEmpty(\getenv('QNULL'));

        self::assertSame('pgsql:host=localhost;dbname=test', $_SERVER['QEQUALS']);
        self::assertSame('test some escaped characters like a quote (") or maybe a backslash (\\)', $_SERVER['QESCAPED']);
        self::assertSame('iiiiviiiixiiiiviiii\\n', $_SERVER['QSLASH']);
        self::assertSame('iiiiviiiixiiiiviiii\\\\n', $_SERVER['SQSLASH']);
    }

    public function testLargeDotenvLoadsEnvironmentVars()
    {
        $dotenv = Dotenv::createMutable(self::$folder, 'large.env');
        $dotenv->load();
        self::assertSame(2730, \strlen($_SERVER['LARGE']));
        self::assertSame(8192, \strlen($_SERVER['HUGE']));
    }

    public function testDotenvLoadsMultibyteVars()
    {
        $dotenv = Dotenv::createMutable(self::$folder, 'multibyte.env');
        $dotenv->load();
        self::assertSame('Ā ā Ă ă Ą ą Ć ć Ĉ ĉ Ċ ċ Č č Ď ď Đ đ Ē ē Ĕ ĕ Ė ė Ę ę Ě ě', $_SERVER['MB1']);
        self::assertSame('行内支付', $_SERVER['MB2']);
        self::assertSame('🚀', $_SERVER['APP_ENV']);
    }

    public function testDotenvLoadsMultibyteUTF8Vars()
    {
        $dotenv = Dotenv::createMutable(self::$folder, 'multibyte.env', false, 'UTF-8');
        $dotenv->load();
        self::assertSame('Ā ā Ă ă Ą ą Ć ć Ĉ ĉ Ċ ċ Č č Ď ď Đ đ Ē ē Ĕ ĕ Ė ė Ę ę Ě ě', $_SERVER['MB1']);
        self::assertSame('行内支付', $_SERVER['MB2']);
        self::assertSame('🚀', $_SERVER['APP_ENV']);
    }

    public function testDotenvLoadWithInvalidEncoding()
    {
        $dotenv = Dotenv::createMutable(self::$folder, 'multibyte.env', false, 'UTF-88');

        $this->expectException(InvalidEncodingException::class);
        $this->expectExceptionMessage('Illegal character encoding [UTF-88] specified.');

        $dotenv->load();
    }

    public function testDotenvLoadsMultibyteWindowsVars()
    {
        $dotenv = Dotenv::createMutable(self::$folder, 'windows.env', false, 'Windows-1252');
        $dotenv->load();
        self::assertSame('ñá', $_SERVER['MBW']);
    }

    public function testMultipleDotenvLoadsEnvironmentVars()
    {
        $dotenv = Dotenv::createMutable(self::$folder, 'multiple.env');
        $dotenv->load();
        self::assertSame('bar', $_SERVER['MULTI1']);
        self::assertSame('foo', $_SERVER['MULTI2']);
    }

    public function testExportedDotenvLoadsEnvironmentVars()
    {
        $dotenv = Dotenv::createMutable(self::$folder, 'exported.env');
        $dotenv->load();
        self::assertSame('bar', $_SERVER['EFOO']);
        self::assertSame('baz', $_SERVER['EBAR']);
        self::assertSame('with spaces', $_SERVER['ESPACED']);
        self::assertSame('123', $_SERVER['EDQUOTED']);
        self::assertSame('456', $_SERVER['ESQUOTED']);
        self::assertEmpty($_SERVER['ENULL']);
    }

    public function testDotenvLoadsEnvGlobals()
    {
        $dotenv = Dotenv::createMutable(self::$folder);
        $dotenv->load();
        self::assertSame('bar', $_SERVER['FOO']);
        self::assertSame('baz', $_SERVER['BAR']);
        self::assertSame('with spaces', $_SERVER['SPACED']);
        self::assertEmpty($_SERVER['NULL']);
    }

    public function testDotenvLoadsServerGlobals()
    {
        $dotenv = Dotenv::createMutable(self::$folder);
        $dotenv->load();
        self::assertSame('bar', $_ENV['FOO']);
        self::assertSame('baz', $_ENV['BAR']);
        self::assertSame('with spaces', $_ENV['SPACED']);
        self::assertEmpty($_ENV['NULL']);
    }

    public function testDotenvNestedEnvironmentVars()
    {
        $dotenv = Dotenv::createMutable(self::$folder, 'nested.env');
        $dotenv->load();
        self::assertSame('{$NVAR1} {$NVAR2}', $_ENV['NVAR3']); // not resolved
        self::assertSame('Hellō World!', $_ENV['NVAR4']);
        self::assertSame('$NVAR1 {NVAR2}', $_ENV['NVAR5']); // not resolved
        self::assertSame('Special Value', $_ENV['N.VAR6']); // new '.' (dot) in var name
        self::assertSame('Special Value', $_ENV['NVAR7']);  // nested '.' (dot) variable
        self::assertSame('', $_ENV['NVAR8']); // nested variable is empty string
        self::assertSame('', $_ENV['NVAR9']); // nested variable is empty string
        self::assertSame('${NVAR888}', $_ENV['NVAR10']); // nested variable is not set
        self::assertSame('NVAR1', $_ENV['NVAR11']);
        self::assertSame('Hellō', $_ENV['NVAR12']);
        self::assertSame('${${NVAR11}}', $_ENV['NVAR13']); // single quotes
        self::assertSame('${NVAR1} ${NVAR2}', $_ENV['NVAR14']); // single quotes
        self::assertSame('${NVAR1} ${NVAR2}', $_ENV['NVAR15']); // escaped
    }

    public function testDotenvNullFileArgumentUsesDefault()
    {
        $dotenv = Dotenv::createMutable(self::$folder, null);
        $dotenv->load();
        self::assertSame('bar', $_SERVER['FOO']);
    }

    /**
     * The fixture data has whitespace between the key and in the value string.
     *
     * Test that these keys are trimmed down.
     */
    public function testDotenvTrimmedKeys()
    {
        $dotenv = Dotenv::createMutable(self::$folder, 'quoted.env');
        $dotenv->load();
        self::assertSame('no space', $_SERVER['QWHITESPACE']);
    }

    public function testDotenvLoadDoesNotOverwriteEnv()
    {
        \putenv('IMMUTABLE=true');
        $dotenv = Dotenv::createImmutable(self::$folder, 'immutable.env');
        $dotenv->load();
        self::assertSame('true', \getenv('IMMUTABLE'));
    }

    public function testDotenvLoadAfterOverload()
    {
        \putenv('IMMUTABLE=true');
        $dotenv = Dotenv::createUnsafeMutable(self::$folder, 'immutable.env');
        $dotenv->load();
        self::assertSame('false', \getenv('IMMUTABLE'));
    }

    public function testDotenvOverloadAfterLoad()
    {
        \putenv('IMMUTABLE=true');
        $dotenv = Dotenv::createUnsafeImmutable(self::$folder, 'immutable.env');
        $dotenv->load();
        self::assertSame('true', \getenv('IMMUTABLE'));
    }

    public function testDotenvOverloadDoesOverwriteEnv()
    {
        $dotenv = Dotenv::createUnsafeMutable(self::$folder, 'mutable.env');
        $dotenv->load();
        self::assertSame('true', \getenv('MUTABLE'));
    }

    public function testDotenvAllowsSpecialCharacters()
    {
        $dotenv = Dotenv::createUnsafeMutable(self::$folder, 'specialchars.env');
        $dotenv->load();
        self::assertSame('$a6^C7k%zs+e^.jvjXk', \getenv('SPVAR1'));
        self::assertSame('?BUty3koaV3%GA*hMAwH}B', \getenv('SPVAR2'));
        self::assertSame('jdgEB4{QgEC]HL))&GcXxokB+wqoN+j>xkV7K?m$r', \getenv('SPVAR3'));
        self::assertSame('22222:22#2^{', \getenv('SPVAR4'));
        self::assertSame('test some escaped characters like a quote " or maybe a backslash \\', \getenv('SPVAR5'));
        self::assertSame('secret!@', \getenv('SPVAR6'));
        self::assertSame('secret!@#', \getenv('SPVAR7'));
        self::assertSame('secret!@#', \getenv('SPVAR8'));
    }

    public function testMultilineLoading()
    {
        $dotenv = Dotenv::createUnsafeMutable(self::$folder, 'multiline.env');
        $dotenv->load();
        self::assertSame("test\n     test\"test\"\n     test", \getenv('TEST'));
        self::assertSame("test\ntest", \getenv('TEST_ND'));
        self::assertSame('test\\ntest', \getenv('TEST_NS'));

        self::assertSame('https://vision.googleapis.com/v1/images:annotate?key=', \getenv('TEST_EQD'));
        self::assertSame('https://vision.googleapis.com/v1/images:annotate?key=', \getenv('TEST_EQS'));
    }

    public function testEmptyLoading()
    {
        $dotenv = Dotenv::createImmutable(self::$folder, 'empty.env');
        self::assertSame(['EMPTY_VAR' => null], $dotenv->load());
    }

    public function testUnicodeVarNames()
    {
        $dotenv = Dotenv::createImmutable(self::$folder, 'unicodevarnames.env');
        $dotenv->load();
        self::assertSame('Skybert', $_SERVER['AlbertÅberg']);
        self::assertSame('2022-04-01T00:00', $_SERVER['ДатаЗакрытияРасчетногоПериода']);
    }

    public function testDirectConstructor()
    {
        $repository = RepositoryBuilder::createWithDefaultAdapters()->make();
        $store = StoreBuilder::createWithDefaultName()->addPath(self::$folder)->make();

        $dotenv = new Dotenv($store, new Parser(), new Loader(), $repository);

        self::assertSame([
            'FOO'    => 'bar',
            'BAR'    => 'baz',
            'SPACED' => 'with spaces',
            'NULL'   => '',
        ], $dotenv->load());
    }

    public function testDotenvParseExample1()
    {
        $output = Dotenv::parse(
            "BASE_DIR=\"/var/webroot/project-root\"\nCACHE_DIR=\"\${BASE_DIR}/cache\"\nTMP_DIR=\"\${BASE_DIR}/tmp\"\n"
        );

        self::assertSame($output, [
            'BASE_DIR'  => '/var/webroot/project-root',
            'CACHE_DIR' => '/var/webroot/project-root/cache',
            'TMP_DIR'   => '/var/webroot/project-root/tmp',
        ]);
    }

    public function testDotenvParseExample2()
    {
        $output = Dotenv::parse("FOO=Bar\nBAZ=\"Hello \${FOO}\"");

        self::assertSame($output, ['FOO' => 'Bar', 'BAZ' => 'Hello Bar']);
    }

    public function testDotenvParseEmptyCase()
    {
        $output = Dotenv::parse('');

        self::assertSame($output, []);
    }
}
