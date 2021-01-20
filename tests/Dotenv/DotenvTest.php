<?php

namespace Dotenv\Tests;

use Dotenv\Dotenv;
use Dotenv\Loader\Loader;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Store\StoreBuilder;
use PHPUnit\Framework\TestCase;

class DotenvTest extends TestCase
{
    /**
     * @var string
     */
    private $folder;

    public function setUp()
    {
        $this->folder = dirname(__DIR__).'/fixtures/env';
    }

    /**
     * @expectedException \Dotenv\Exception\InvalidPathException
     * @expectedExceptionMessage Unable to read any of the environment file(s) at
     */
    public function testDotenvThrowsExceptionIfUnableToLoadFile()
    {
        $dotenv = Dotenv::createImmutable(__DIR__);
        $dotenv->load();
    }

    /**
     * @expectedException \Dotenv\Exception\InvalidPathException
     * @expectedExceptionMessage Unable to read any of the environment file(s) at
     */
    public function testDotenvThrowsExceptionIfUnableToLoadFiles()
    {
        $dotenv = Dotenv::createImmutable([__DIR__, __DIR__.'/foo/bar']);
        $dotenv->load();
    }

    /**
     * @expectedException \Dotenv\Exception\InvalidPathException
     * @expectedExceptionMessage At least one environment file path must be provided.
     */
    public function testDotenvThrowsExceptionWhenNoFiles()
    {
        $dotenv = Dotenv::createImmutable([]);
        $dotenv->load();
    }

    public function testDotenvTriesPathsToLoad()
    {
        $dotenv = Dotenv::createImmutable([__DIR__, $this->folder]);
        self::assertCount(4, $dotenv->load());
    }

    public function testDotenvTriesPathsToSafeLoad()
    {
        $dotenv = Dotenv::createImmutable([__DIR__, $this->folder]);
        self::assertCount(4, $dotenv->safeLoad());
    }

    public function testDotenvSkipsLoadingIfFileIsMissing()
    {
        $dotenv = Dotenv::createImmutable(__DIR__);
        self::assertSame([], $dotenv->safeLoad());
    }

    public function testDotenvLoadsEnvironmentVars()
    {
        $dotenv = Dotenv::createImmutable($this->folder);
        self::assertSame(
            ['FOO' => 'bar', 'BAR' => 'baz', 'SPACED' => 'with spaces', 'NULL' => ''],
            $dotenv->load()
        );
        self::assertSame('bar', getenv('FOO'));
        self::assertSame('baz', getenv('BAR'));
        self::assertSame('with spaces', getenv('SPACED'));
        self::assertEmpty(getenv('NULL'));
    }

    public function testDotenvLoadsEnvironmentVarsMultipleNotShortCircuitMode()
    {
        $dotenv = Dotenv::createImmutable($this->folder, ['.env', 'example.env']);

        self::assertSame(
            ['FOO' => 'bar', 'BAR' => 'baz', 'SPACED' => 'with spaces', 'NULL' => ''],
            $dotenv->load()
        );
    }

    public function testDotenvLoadsEnvironmentVarsMultipleWithShortCircuitMode()
    {
        $dotenv = Dotenv::createImmutable($this->folder, ['.env', 'example.env'], false);

        self::assertSame(
            ['FOO' => 'bar', 'BAR' => 'baz', 'SPACED' => 'with spaces', 'NULL' => '', 'EG' => 'example'],
            $dotenv->load()
        );
    }

    public function testCommentedDotenvLoadsEnvironmentVars()
    {
        $dotenv = Dotenv::createImmutable($this->folder, 'commented.env');
        $dotenv->load();
        self::assertSame('bar', getenv('CFOO'));
        self::assertFalse(getenv('CBAR'));
        self::assertFalse(getenv('CZOO'));
        self::assertSame('with spaces', getenv('CSPACED'));
        self::assertSame('a value with a # character', getenv('CQUOTES'));
        self::assertSame('a value with a # character & a quote " character inside quotes', getenv('CQUOTESWITHQUOTE'));
        self::assertEmpty(getenv('CNULL'));
        self::assertEmpty(getenv('EMPTY'));
        self::assertEmpty(getenv('EMPTY2'));
        self::assertSame('foo', getenv('FOOO'));
    }

    public function testQuotedDotenvLoadsEnvironmentVars()
    {
        $dotenv = Dotenv::createImmutable($this->folder, 'quoted.env');
        $dotenv->load();
        self::assertSame('bar', getenv('QFOO'));
        self::assertSame('baz', getenv('QBAR'));
        self::assertSame('with spaces', getenv('QSPACED'));
        self::assertEmpty(getenv('QNULL'));

        self::assertSame('pgsql:host=localhost;dbname=test', getenv('QEQUALS'));
        self::assertSame('test some escaped characters like a quote (") or maybe a backslash (\\)', getenv('QESCAPED'));
        self::assertSame('iiiiviiiixiiiiviiii\\n', getenv('QSLASH'));
        self::assertSame('iiiiviiiixiiiiviiii\\\\n', getenv('SQSLASH'));
    }

    public function testLargeDotenvLoadsEnvironmentVars()
    {
        $dotenv = Dotenv::createImmutable($this->folder, 'large.env');
        $dotenv->load();
        self::assertNotEmpty(getenv('LARGE'));
    }

    public function testMultipleDotenvLoadsEnvironmentVars()
    {
        $dotenv = Dotenv::createImmutable($this->folder, 'multiple.env');
        $dotenv->load();
        self::assertSame('bar', getenv('MULTI1'));
        self::assertSame('foo', getenv('MULTI2'));
    }

    public function testExportedDotenvLoadsEnvironmentVars()
    {
        $dotenv = Dotenv::createImmutable($this->folder, 'exported.env');
        $dotenv->load();
        self::assertSame('bar', getenv('EFOO'));
        self::assertSame('baz', getenv('EBAR'));
        self::assertSame('with spaces', getenv('ESPACED'));
        self::assertEmpty(getenv('ENULL'));
    }

    public function testDotenvLoadsEnvGlobals()
    {
        $dotenv = Dotenv::createImmutable($this->folder);
        $dotenv->load();
        self::assertSame('bar', $_SERVER['FOO']);
        self::assertSame('baz', $_SERVER['BAR']);
        self::assertSame('with spaces', $_SERVER['SPACED']);
        self::assertEmpty($_SERVER['NULL']);
    }

    public function testDotenvLoadsServerGlobals()
    {
        $dotenv = Dotenv::createImmutable($this->folder);
        $dotenv->load();
        self::assertSame('bar', $_ENV['FOO']);
        self::assertSame('baz', $_ENV['BAR']);
        self::assertSame('with spaces', $_ENV['SPACED']);
        self::assertEmpty($_ENV['NULL']);
    }

    public function testDotenvNestedEnvironmentVars()
    {
        $dotenv = Dotenv::createImmutable($this->folder, 'nested.env');
        $dotenv->load();
        self::assertSame('{$NVAR1} {$NVAR2}', $_ENV['NVAR3']); // not resolved
        self::assertSame('Hello World!', $_ENV['NVAR4']);
        self::assertSame('$NVAR1 {NVAR2}', $_ENV['NVAR5']); // not resolved
        self::assertSame('Special Value', $_ENV['N.VAR6']); // new '.' (dot) in var name
        self::assertSame('Special Value', $_ENV['NVAR7']);  // nested '.' (dot) variable
        self::assertSame('', $_ENV['NVAR8']); // nested variable is empty string
        self::assertSame('', $_ENV['NVAR9']); // nested variable is empty string
        self::assertSame('${NVAR888}', $_ENV['NVAR10']); // nested variable is not set
        self::assertSame('NVAR1', $_ENV['NVAR11']);
        self::assertSame('Hello', $_ENV['NVAR12']);
        self::assertSame('${${NVAR11}}', $_ENV['NVAR13']); // single quotes
        self::assertSame('${NVAR1} ${NVAR2}', $_ENV['NVAR14']); // single quotes
        self::assertSame('${NVAR1} ${NVAR2}', $_ENV['NVAR15']); // escaped
    }

    public function testDotenvNullFileArgumentUsesDefault()
    {
        $dotenv = Dotenv::createImmutable($this->folder, null);
        $dotenv->load();
        self::assertSame('bar', getenv('FOO'));
    }

    /**
     * The fixture data has whitespace between the key and in the value string.
     *
     * Test that these keys are trimmed down.
     */
    public function testDotenvTrimmedKeys()
    {
        $dotenv = Dotenv::createImmutable($this->folder, 'quoted.env');
        $dotenv->load();
        self::assertSame('no space', getenv('QWHITESPACE'));
    }

    public function testDotenvLoadDoesNotOverwriteEnv()
    {
        putenv('IMMUTABLE=true');
        $dotenv = Dotenv::createImmutable($this->folder, 'immutable.env');
        $dotenv->load();
        self::assertSame('true', getenv('IMMUTABLE'));
    }

    public function testDotenvLoadAfterOverload()
    {
        putenv('IMMUTABLE=true');
        $dotenv = Dotenv::createMutable($this->folder, 'immutable.env');
        $dotenv->load();
        self::assertSame('false', getenv('IMMUTABLE'));
    }

    public function testDotenvOverloadAfterLoad()
    {
        putenv('IMMUTABLE=true');
        $dotenv = Dotenv::createImmutable($this->folder, 'immutable.env');
        $dotenv->load();
        self::assertSame('true', getenv('IMMUTABLE'));
    }

    public function testDotenvOverloadDoesOverwriteEnv()
    {
        $dotenv = Dotenv::createMutable($this->folder, 'mutable.env');
        $dotenv->load();
        self::assertSame('true', getenv('MUTABLE'));
    }

    public function testDotenvAllowsSpecialCharacters()
    {
        $dotenv = Dotenv::createImmutable($this->folder, 'specialchars.env');
        $dotenv->load();
        self::assertSame('$a6^C7k%zs+e^.jvjXk', getenv('SPVAR1'));
        self::assertSame('?BUty3koaV3%GA*hMAwH}B', getenv('SPVAR2'));
        self::assertSame('jdgEB4{QgEC]HL))&GcXxokB+wqoN+j>xkV7K?m$r', getenv('SPVAR3'));
        self::assertSame('22222:22#2^{', getenv('SPVAR4'));
        self::assertSame('test some escaped characters like a quote " or maybe a backslash \\', getenv('SPVAR5'));
        self::assertSame('secret!@', getenv('SPVAR6'));
        self::assertSame('secret!@#', getenv('SPVAR7'));
        self::assertSame('secret!@#', getenv('SPVAR8'));
    }

    public function testMutlilineLoading()
    {
        $dotenv = Dotenv::createImmutable($this->folder, 'multiline.env');
        $dotenv->load();
        self::assertSame("test\n     test\"test\"\n     test", getenv('TEST'));
        self::assertSame("test\ntest", getenv('TEST_ND'));
        self::assertSame('test\\ntest', getenv('TEST_NS'));

        self::assertSame('https://vision.googleapis.com/v1/images:annotate?key=', getenv('TEST_EQD'));
        self::assertSame('https://vision.googleapis.com/v1/images:annotate?key=', getenv('TEST_EQS'));
    }

    public function testEmptyLoading()
    {
        $dotenv = Dotenv::createImmutable($this->folder, 'empty.env');
        self::assertSame(['EMPTY_VAR' => null], $dotenv->load());
    }

    public function testLegacyConstructor()
    {
        $loader = new Loader();
        $repository = RepositoryBuilder::create()->immutable()->make();

        $dotenv = new Dotenv($loader, $repository, [$this->folder.DIRECTORY_SEPARATOR.'.env']);

        self::assertSame([
            'FOO'    => 'bar',
            'BAR'    => 'baz',
            'SPACED' => 'with spaces',
            'NULL'   => '',
        ], $dotenv->load());
    }

    public function testLatestConstructor()
    {
        $loader = new Loader();
        $repository = RepositoryBuilder::create()->immutable()->make();
        $store = StoreBuilder::create()->withPaths($this->folder)->make();

        $dotenv = new Dotenv($loader, $repository, $store);

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
