<?php

declare(strict_types=1);

namespace Dotenv\Tests;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Dotenv\Loader\Loader;
use Dotenv\Parser\Parser;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Store\StoreBuilder;
use PHPUnit\Framework\TestCase;

class DotenvTest extends TestCase
{
    /**
     * @var string
     */
    private static $folder;

    /**
     * @beforeClass
     */
    public static function setFolder()
    {
        self::$folder = dirname(__DIR__).'/fixtures/env';
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
        $this->assertCount(4, $dotenv->load());
    }

    public function testDotenvTriesPathsToLoadTwice()
    {
        $dotenv = Dotenv::createMutable([__DIR__, self::$folder]);
        $this->assertCount(4, $dotenv->load());

        $dotenv = Dotenv::createImmutable([__DIR__, self::$folder]);
        $this->assertCount(0, $dotenv->load());
    }

    public function testDotenvTriesPathsToSafeLoad()
    {
        $dotenv = Dotenv::createMutable([__DIR__, self::$folder]);
        $this->assertCount(4, $dotenv->safeLoad());
    }

    public function testDotenvSkipsLoadingIfFileIsMissing()
    {
        $dotenv = Dotenv::createMutable(__DIR__);
        $this->assertSame([], $dotenv->safeLoad());
    }

    public function testDotenvLoadsEnvironmentVars()
    {
        $dotenv = Dotenv::createMutable(self::$folder);
        $this->assertSame(
            ['FOO' => 'bar', 'BAR' => 'baz', 'SPACED' => 'with spaces', 'NULL' => ''],
            $dotenv->load()
        );
        $this->assertSame('bar', $_SERVER['FOO']);
        $this->assertSame('baz', $_SERVER['BAR']);
        $this->assertSame('with spaces', $_SERVER['SPACED']);
        $this->assertEmpty($_SERVER['NULL']);
    }

    public function testDotenvLoadsEnvironmentVarsMultipleNotShortCircuitMode()
    {
        $dotenv = Dotenv::createMutable(self::$folder, ['.env', 'example.env']);

        $this->assertSame(
            ['FOO' => 'bar', 'BAR' => 'baz', 'SPACED' => 'with spaces', 'NULL' => ''],
            $dotenv->load()
        );
    }

    public function testDotenvLoadsEnvironmentVarsMultipleWithShortCircuitMode()
    {
        $dotenv = Dotenv::createMutable(self::$folder, ['.env', 'example.env'], false);

        $this->assertSame(
            ['FOO' => 'bar', 'BAR' => 'baz', 'SPACED' => 'with spaces', 'NULL' => '', 'EG' => 'example'],
            $dotenv->load()
        );
    }

    public function testCommentedDotenvLoadsEnvironmentVars()
    {
        $dotenv = Dotenv::createMutable(self::$folder, 'commented.env');
        $dotenv->load();
        $this->assertSame('bar', $_SERVER['CFOO']);
        $this->assertFalse(isset($_SERVER['CBAR']));
        $this->assertFalse(isset($_SERVER['CZOO']));
        $this->assertSame('with spaces', $_SERVER['CSPACED']);
        $this->assertSame('a value with a # character', $_SERVER['CQUOTES']);
        $this->assertSame('a value with a # character & a quote " character inside quotes', $_SERVER['CQUOTESWITHQUOTE']);
        $this->assertEmpty($_SERVER['CNULL']);
        $this->assertEmpty($_SERVER['EMPTY']);
        $this->assertEmpty($_SERVER['EMPTY2']);
        $this->assertSame('foo', $_SERVER['FOOO']);
    }

    public function testQuotedDotenvLoadsEnvironmentVars()
    {
        $dotenv = Dotenv::createMutable(self::$folder, 'quoted.env');
        $dotenv->load();
        $this->assertSame('bar', $_SERVER['QFOO']);
        $this->assertSame('baz', $_SERVER['QBAR']);
        $this->assertSame('with spaces', $_SERVER['QSPACED']);
        $this->assertEmpty(getenv('QNULL'));

        $this->assertSame('pgsql:host=localhost;dbname=test', $_SERVER['QEQUALS']);
        $this->assertSame('test some escaped characters like a quote (") or maybe a backslash (\\)', $_SERVER['QESCAPED']);
        $this->assertSame('iiiiviiiixiiiiviiii\\n', $_SERVER['QSLASH']);
        $this->assertSame('iiiiviiiixiiiiviiii\\\\n', $_SERVER['SQSLASH']);
    }

    public function testLargeDotenvLoadsEnvironmentVars()
    {
        $dotenv = Dotenv::createMutable(self::$folder, 'large.env');
        $dotenv->load();
        $this->assertNotEmpty($_SERVER['LARGE']);
    }

    public function testMultipleDotenvLoadsEnvironmentVars()
    {
        $dotenv = Dotenv::createMutable(self::$folder, 'multiple.env');
        $dotenv->load();
        $this->assertSame('bar', $_SERVER['MULTI1']);
        $this->assertSame('foo', $_SERVER['MULTI2']);
    }

    public function testExportedDotenvLoadsEnvironmentVars()
    {
        $dotenv = Dotenv::createMutable(self::$folder, 'exported.env');
        $dotenv->load();
        $this->assertSame('bar', $_SERVER['EFOO']);
        $this->assertSame('baz', $_SERVER['EBAR']);
        $this->assertSame('with spaces', $_SERVER['ESPACED']);
        $this->assertEmpty($_SERVER['ENULL']);
    }

    public function testDotenvLoadsEnvGlobals()
    {
        $dotenv = Dotenv::createMutable(self::$folder);
        $dotenv->load();
        $this->assertSame('bar', $_SERVER['FOO']);
        $this->assertSame('baz', $_SERVER['BAR']);
        $this->assertSame('with spaces', $_SERVER['SPACED']);
        $this->assertEmpty($_SERVER['NULL']);
    }

    public function testDotenvLoadsServerGlobals()
    {
        $dotenv = Dotenv::createMutable(self::$folder);
        $dotenv->load();
        $this->assertSame('bar', $_ENV['FOO']);
        $this->assertSame('baz', $_ENV['BAR']);
        $this->assertSame('with spaces', $_ENV['SPACED']);
        $this->assertEmpty($_ENV['NULL']);
    }

    public function testDotenvNestedEnvironmentVars()
    {
        $dotenv = Dotenv::createMutable(self::$folder, 'nested.env');
        $dotenv->load();
        $this->assertSame('{$NVAR1} {$NVAR2}', $_ENV['NVAR3']); // not resolved
        $this->assertSame('Hello World!', $_ENV['NVAR4']);
        $this->assertSame('$NVAR1 {NVAR2}', $_ENV['NVAR5']); // not resolved
        $this->assertSame('Special Value', $_ENV['N.VAR6']); // new '.' (dot) in var name
        $this->assertSame('Special Value', $_ENV['NVAR7']);  // nested '.' (dot) variable
        $this->assertSame('', $_ENV['NVAR8']); // nested variable is empty string
        $this->assertSame('', $_ENV['NVAR9']); // nested variable is empty string
        $this->assertSame('${NVAR888}', $_ENV['NVAR10']); // nested variable is not set
        $this->assertSame('NVAR1', $_ENV['NVAR11']);
        $this->assertSame('Hello', $_ENV['NVAR12']);
        $this->assertSame('${${NVAR11}}', $_ENV['NVAR13']); // single quotes
        $this->assertSame('${NVAR1} ${NVAR2}', $_ENV['NVAR14']); // single quotes
        $this->assertSame('${NVAR1} ${NVAR2}', $_ENV['NVAR15']); // escaped
    }

    public function testDotenvNullFileArgumentUsesDefault()
    {
        $dotenv = Dotenv::createMutable(self::$folder, null);
        $dotenv->load();
        $this->assertSame('bar', $_SERVER['FOO']);
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
        $this->assertSame('no space', $_SERVER['QWHITESPACE']);
    }

    public function testDotenvLoadDoesNotOverwriteEnv()
    {
        putenv('IMMUTABLE=true');
        $dotenv = Dotenv::createImmutable(self::$folder, 'immutable.env');
        $dotenv->load();
        $this->assertSame('true', getenv('IMMUTABLE'));
    }

    public function testDotenvLoadAfterOverload()
    {
        putenv('IMMUTABLE=true');
        $dotenv = Dotenv::createUnsafeMutable(self::$folder, 'immutable.env');
        $dotenv->load();
        $this->assertSame('false', getenv('IMMUTABLE'));
    }

    public function testDotenvOverloadAfterLoad()
    {
        putenv('IMMUTABLE=true');
        $dotenv = Dotenv::createUnsafeImmutable(self::$folder, 'immutable.env');
        $dotenv->load();
        $this->assertSame('true', getenv('IMMUTABLE'));
    }

    public function testDotenvOverloadDoesOverwriteEnv()
    {
        $dotenv = Dotenv::createUnsafeMutable(self::$folder, 'mutable.env');
        $dotenv->load();
        $this->assertSame('true', getenv('MUTABLE'));
    }

    public function testDotenvAllowsSpecialCharacters()
    {
        $dotenv = Dotenv::createUnsafeMutable(self::$folder, 'specialchars.env');
        $dotenv->load();
        $this->assertSame('$a6^C7k%zs+e^.jvjXk', getenv('SPVAR1'));
        $this->assertSame('?BUty3koaV3%GA*hMAwH}B', getenv('SPVAR2'));
        $this->assertSame('jdgEB4{QgEC]HL))&GcXxokB+wqoN+j>xkV7K?m$r', getenv('SPVAR3'));
        $this->assertSame('22222:22#2^{', getenv('SPVAR4'));
        $this->assertSame('test some escaped characters like a quote " or maybe a backslash \\', getenv('SPVAR5'));
        $this->assertSame('secret!@', getenv('SPVAR6'));
        $this->assertSame('secret!@#', getenv('SPVAR7'));
        $this->assertSame('secret!@#', getenv('SPVAR8'));
    }

    public function testMutlilineLoading()
    {
        $dotenv = Dotenv::createUnsafeMutable(self::$folder, 'multiline.env');
        $dotenv->load();
        $this->assertSame("test\n     test\"test\"\n     test", getenv('TEST'));
        $this->assertSame("test\ntest", getenv('TEST_ND'));
        $this->assertSame('test\\ntest', getenv('TEST_NS'));

        $this->assertSame('https://vision.googleapis.com/v1/images:annotate?key=', getenv('TEST_EQD'));
        $this->assertSame('https://vision.googleapis.com/v1/images:annotate?key=', getenv('TEST_EQS'));
    }

    public function testEmptyLoading()
    {
        $dotenv = Dotenv::createImmutable(self::$folder, 'empty.env');
        $this->assertSame(['EMPTY_VAR' => null], $dotenv->load());
    }

    public function testDirectConstructor()
    {
        $loader = new Loader(new Parser());
        $repository = RepositoryBuilder::createWithDefaultAdapters()->make();
        $store = StoreBuilder::createWithDefaultName()->addPath(self::$folder)->make();

        $dotenv = new Dotenv($loader, $repository, $store);

        $this->assertSame([
            'FOO'    => 'bar',
            'BAR'    => 'baz',
            'SPACED' => 'with spaces',
            'NULL'   => '',
        ], $dotenv->load());
    }
}
