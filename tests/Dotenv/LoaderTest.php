<?php

use Dotenv\Loader\Loader;
use Dotenv\Repository\Adapter\ArrayAdapter;
use Dotenv\Repository\Adapter\EnvConstAdapter;
use Dotenv\Repository\Adapter\ServerConstAdapter;
use Dotenv\Repository\RepositoryBuilder;
use PHPUnit\Framework\TestCase;

class LoaderTest extends TestCase
{
    public function testLoaderWithNoReaders()
    {
        $repository = RepositoryBuilder::create()->withReaders([])->make();
        $loader = new Loader();

        $content = "NVAR1=\"Hello\"\nNVAR2=\"World!\"\nNVAR3=\"{\$NVAR1} {\$NVAR2}\"\nNVAR4=\"\${NVAR1} \${NVAR2}\"";
        $expected = ['NVAR1' => 'Hello', 'NVAR2' => 'World!', 'NVAR3' => '{$NVAR1} {$NVAR2}', 'NVAR4' => '${NVAR1} ${NVAR2}'];

        $this->assertSame($expected, $loader->load($repository, $content));
    }

    public function testLoaderWithWhitelist()
    {
        $adapter = new ArrayAdapter();
        $repository = RepositoryBuilder::create()->withReaders([$adapter])->withWriters([$adapter])->make();
        $loader = new Loader(['FOO']);

        $this->assertSame(['FOO' => 'Hello'], $loader->load($repository, "FOO=\"Hello\"\nBAR=\"World!\"\n"));
        $this->assertTrue($adapter->get('FOO')->isDefined());
        $this->assertSame('Hello', $adapter->get('FOO')->get());
        $this->assertFalse($adapter->get('BAR')->isDefined());
    }

    public function providesAdapters()
    {
        return [
            [null],
            [[new ArrayAdapter()]],
            [[new EnvConstAdapter()]],
            [[new ServerConstAdapter()]],
        ];
    }

    /**
     * @dataProvider providesAdapters
     */
    public function testLoaderWithSpecificAdapter($adapters)
    {
        $repository = RepositoryBuilder::create()->withReaders($adapters)->withWriters($adapters)->make();
        $loader = new Loader();

        $content = "NVAR1=\"Hello\"\nNVAR2=\"World!\"\nNVAR3=\"{\$NVAR1} {\$NVAR2}\"\nNVAR4=\"\${NVAR1} \${NVAR2}\"";
        $expected = ['NVAR1' => 'Hello', 'NVAR2' => 'World!', 'NVAR3' => '{$NVAR1} {$NVAR2}', 'NVAR4' => 'Hello World!'];

        $this->assertSame($expected, $loader->load($repository, $content));
    }
}
