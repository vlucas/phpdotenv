<?php

declare(strict_types=1);

namespace Dotenv\Tests;

use Dotenv\Exception\InvalidFileException;
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
        $repository = RepositoryBuilder::createWithNoAdapters()->addWriter(ArrayAdapter::class)->make();
        $loader = new Loader();

        $content = "NVAR1=\"Hello\"\nNVAR2=\"World!\"\nNVAR3=\"{\$NVAR1} {\$NVAR2}\"\nNVAR4=\"\${NVAR1} \${NVAR2}\"";
        $expected = ['NVAR1' => 'Hello', 'NVAR2' => 'World!', 'NVAR3' => '{$NVAR1} {$NVAR2}', 'NVAR4' => '${NVAR1} ${NVAR2}'];

        $this->assertSame($expected, $loader->load($repository, $content));
    }

    public function testLoaderWithWhitelist()
    {
        $adapter = ArrayAdapter::create()->get();
        $repository = RepositoryBuilder::createWithNoAdapters()->addReader($adapter)->addWriter($adapter)->whitelist(['FOO'])->make();
        $loader = new Loader();

        $this->assertSame(['FOO' => 'Hello'], $loader->load($repository, "FOO=\"Hello\"\nBAR=\"World!\"\n"));
        $this->assertTrue($adapter->get('FOO')->isDefined());
        $this->assertSame('Hello', $adapter->get('FOO')->get());
        $this->assertFalse($adapter->get('BAR')->isDefined());
    }

    public function testLoaderWithGarbage()
    {
        $adapter = ArrayAdapter::create()->get();
        $repository = RepositoryBuilder::createWithNoAdapters()->make();
        $loader = new Loader();

        $this->expectException(InvalidFileException::class);
        $this->expectExceptionMessage('Failed to parse dotenv file due to unexpected whitespace. Failed at ["""].');

        $loader->load($repository, 'FOO="""');
    }

    public function providesAdapters()
    {
        return [
            [ArrayAdapter::create()->get()],
            [EnvConstAdapter::class],
            [ServerConstAdapter::class],
        ];
    }

    /**
     * @dataProvider providesAdapters
     */
    public function testLoaderWithSpecificAdapter($adapter)
    {
        $repository = RepositoryBuilder::createWithNoAdapters()->addReader($adapter)->addWriter($adapter)->make();
        $loader = new Loader();

        $content = "NVAR1=\"Hello\"\nNVAR2=\"World!\"\nNVAR3=\"{\$NVAR1} {\$NVAR2}\"\nNVAR4=\"\${NVAR1} \${NVAR2}\"";
        $expected = ['NVAR1' => 'Hello', 'NVAR2' => 'World!', 'NVAR3' => '{$NVAR1} {$NVAR2}', 'NVAR4' => 'Hello World!'];

        $this->assertSame($expected, $loader->load($repository, $content));
    }
}
