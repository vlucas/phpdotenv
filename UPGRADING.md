# Upgrading Guide

## V5.5 to V5.6

Bumping the minimum required PHP version is not a breaking change, however it is notable. Since version 5.6.0, we now require PHP 7.2.5 or higher. Installation metrics show that for some time, PHP 7.1 has represented only around 0.1% of installs of V5.

Release notes for 5.6.0 are available [here](https://github.com/vlucas/phpdotenv/releases/tag/v5.6.0).

## V4 to V5

### Introduction

Version 5 bumps to PHP 7.1+, and adds some additional parameter typing. There have been some internal changes and refactorings too, but nothing that changes the overall feel and usage of the package. The Dotenv class itself is largely unchanged from V4.

Release notes for 5.0.0 are available [here](https://github.com/vlucas/phpdotenv/releases/tag/v5.0.0).

### Details

1. The `Dotenv\Dotenv::createImmutable` and `Dotenv\Dotenv::createMutable` methods no longer call will result in `getenv` and `putenv` being called. One should instead use `Dotenv\Dotenv::createUnsafeImmutable` and `Dotenv\Dotenv::createUnsafeMutable` methods, if one really needs these functions.
2. The `Dotenv\Dotenv` constructor has been modified to expect exactly 4 parameters: a store, a parser, a loader, and a repository. This likely won't affect many people, since it is more common to construct this class via the public static create methods. Those methods have not changed.
3. Scalar typehints have been added to the public interface.
4. The parser now returns a result type instead of raising an exception. This change is strictly internal, and most users won't notice a difference. The responsibility for raising an exception has simply been shifted up to the caller.
5. Adapters have been refactored again, with changes to the repositories. In particular, the repository builder has been tweaked. It now expects to be explicitly told if you want to use the default adapters or not, and expects individual readers and writers to be added, one by one. Similar changes have been applied to the store factory. Moreover, the `ApacheAdapter` has been changed so that it behaves much like the other adapters. The old behaviour can be simulated by composing it with the new `ReplacingWriter` (see below). We will no longer include this adapter in our default setup, so that people can enable exactly what they need. Finally, by default, we will no longer be using the `PutenvAdapter`. It can be added, as required.
6. Variable whitelisting has been replaced with allow listing, and the responsibility has moved from the loader to a new adapter `GuardedWriter`.
7. The parser has been moved to its own namespace and parses entire files now. This change is expected to have little impact when upgrading. The `Lines` class has also moved to the parser namespace.
8. The loader now only returns the variables that were actually loaded into the repository, and not all the variables from the file. Moreover, it now expects as input the result of running the new parser (an array of entries), rather than raw file content.

The changes listed in (4) mean that instead of:

```php
$repository = Dotenv\Repository\RepositoryBuilder::create()
    ->withReaders([
        new Dotenv\Repository\Adapter\EnvConstAdapter(),
    ])
    ->withWriters([
        new Dotenv\Repository\Adapter\EnvConstAdapter(),
        new Dotenv\Repository\Adapter\PutenvAdapter(),
    ])
    ->make();
```

one would now write:

```php
$repository = Dotenv\Repository\RepositoryBuilder::createWithNoAdapters()
    ->addAdapter(Dotenv\Repository\Adapter\EnvConstAdapter::class)
    ->addWriter(Dotenv\Repository\Adapter\PutenvAdapter::class)
    ->make();
```

Instead of passing class names, one can also pass actual adapter instances. Note that it is not possible to directly construct any of the adapters. One has to go via their static `create` method which returns an optional. This is to strictly encapsulate the fact that not all adapters are capable of running on all systems, and so those that cannot be run, cannot be created. For example, the apache adapter can only be run within an apache web server context. Passing the class names as in the above example will handle this for you, by adding the adapter only if it can be created (the optional has a value set).

To add an apache environment variable writer that only writes to existing apache environment variables, as was the default in v4, one should do the following:

```php
$builder = Dotenv\Repository\RepositoryBuilder::createWithDefaultAdapters();

Dotenv\Repository\Adapter\ApacheAdapter::create()->map(function ($adapter) {
    return new Dotenv\Repository\Adapter\ReplacingWriter($adapter, $adapter);
})->map([$builder, 'addWriter'])->getOrElse($builder);

$repository = $builder->make();
```

The use of optionals handles the case where the apache environment functions are not available (such as in a CLI environment).

## V4.0 to V4.1

### Introduction

Version 4.1 is a minor release, and as such, there are no breaking changes. There is, however a deprecation to be noted.

### Details

The `Dotenv\Dotenv` constructor now expects either an array of file paths as the third parameter, or an instance of `Dotenv\Store\StoreInterface`. Passing an array is deprecated, and will be removed in V5.

## V3 to V4

### Introduction

Version 4 sees some refactoring, and support for escaping dollars in values (https://github.com/vlucas/phpdotenv/pull/380). It is no longer possible to change immutability on the fly, and the `Loader` no longer is responsible for tracking immutability. It is now the responsibility of "repositories" to track this. One must explicitly decide if they want (im)mutability when constructing an instance of `Dotenv\Dotenv`.

Release notes for 4.0.0 are available [here](https://github.com/vlucas/phpdotenv/releases/tag/v4.0.0).

### Details

V4 has again changed the way you initialize the `Dotenv` class. If you want immutable loading of environment variables, then replace `Dotenv::create` with `Dotenv::createImmutable`, and if you want mutable loading, replace `Dotenv::create` with `Dotenv::createMutable` and `->overload()` with `->load()`. The `overload` method has been removed in favour of specifying mutability at object construction.

The behaviour when parsing single quoted strings has now changed, to mimic the behaviour of bash. It is no longer possible to escape characters in single quoted strings, and everything is treated literally. As soon as the first single quote character is read, after the initial one, then the variable is treated as ending immediately at that point. When parsing unquoted or double quoted strings, it is now possible to escape dollar signs, to forcefully avoid variable interpolation. Escaping dollars is not mandated, in the sense that if a dollar is present, and not following by variable interpolation syntax, this is allowed, and the dollar will be treated as a literal dollar. Finally, interpolation of variables is now performed right to left, instead of left to right, so it is possible to nest interpolations to allow using the value of a variable as the name of another for further interpolation.

The `getEnvironmentVariableNames` method is no longer available. This is because calls to `load()` (since v3.0.0) return an associative array of what was loaded, so `$dotenv->getEnvironmentVariableNames()` can be replaced with `array_keys($dotenv->load())`.

There have been various internal refactorings. Apart from what has already been mentioned, the only other changes likely to affect developers is:

1. The `Dotenv\Environment` namespace has been moved to `Dotenv\Repository`, the `Dotenv\Environment\Adapter\AdapterInterface` interface has been replaced by `Dotenv\Repository\Adapter\ReaderInterface` and `Dotenv\Repository\Adapter\WriterInterface`.
2. The `Dotenv\Environment\DotenvFactory` has been (roughly) replaced by `Dotenv\Repository\RepositoryBuilder`, and `Dotenv\Environment\FactoryInterface` has been deleted.
3. `Dotenv\Environment\AbstractVariables` has been replaced by `Dotenv\Repository\AbstractRepository`, `Dotenv\Environment\DotenvVariables` has been replaced by `Dotenv\Repository\AdapterRepository`, and `Dotenv\Environment\VariablesInterface` has been replaced by `Dotenv\Repository\RepositoryInterface`.
4. The `Dotenv\Loader` class has been moved to `Dotenv\Loader\Loader`, and now has a different public interface. It no longer expects any parameters at construction, and implements only the new interface `Dotenv\Loader\LoaderInterface`. Its responsibility has changed to purely taking raw env file content, and handing it off to the parser, dealing with variable interpolation, and sending off instructions to the repository to set variables. No longer can it be used as a way to read the environment by callers, and nor does it track immutability.
5. The `Dotenv\Parser` and `Dotenv\Lines` classes have moved to `Dotenv\Loader\Parser` and `Dotenv\Loader\Lines`, respectively. `Dotenv\Loader\Parser::parse` now return has either `null` or `Dotenv\Loader\Value` objects as values, instead of `string`s. This is to support the new variable interpolation and dollar escaping features.
6. The `Dotenv\Validator` constructor has changed from `__construct(array $variables, Loader $loader, $required = true)` to `__construct(RepositoryInterface $repository, array $variables, $required = true)`.

The example at the bottom of the below upgrading guide, in V4 now looks like:

```php
<?php

use Dotenv\Dotenv;
use Dotenv\Repository\Adapter\EnvConstAdapter;
use Dotenv\Repository\Adapter\ServerConstAdapter;
use Dotenv\Repository\RepositoryBuilder;

$adapters = [
	new EnvConstAdapter(),
	new ServerConstAdapter(),
];

$repository = RepositoryBuilder::create()
    ->withReaders($adapters)
    ->withWriters($adapters)
    ->immutable()
    ->make();

Dotenv::create($repository, $path, null)->load();
```

Since v3.2.0, it was easily possible to read a file and process variable interpolations, without actually "loading" the variables. This is still possible in v4.0.0. Example code that does this is as follows:

```php
<?php

use Dotenv\Repository\Adapter\ArrayAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Loader\Loader;

$adapters = [new ArrayAdapter()];

$repository = RepositoryBuilder::create()
    ->withReaders($adapters)
    ->withWriters($adapters)
    ->make();

$variables = (new Loader())->load($repository, $content);
```

Notice, that compared to v3, the loader no longer expects file paths in the constructor. Reading of the files is now managed by the `Dotenv\Dotenv` class. The loader is genuinely just loading the content into the repository.

Finally, we note that the minimum supported version of PHP has increased to 5.5.9, up from 5.4.0 in V3 and 5.3.9 in V2.

## V2 to V3

### Introduction

New in Version 3 is first-class support for multiline variables ([#301](https://github.com/vlucas/phpdotenv/pull/301)) and much more flexibility in terms of which parts of the environment we try to read and modify ([#300](https://github.com/vlucas/phpdotenv/pull/300)). Consequently, you will need to replace any occurrences of `new Dotenv(...)` with `Dotenv::create(...)`, since our new native constructor takes a `Loader` instance now, so that it can be truly customized if required. Finally, one should note that the loader will no longer be trimming values ([#302](https://github.com/vlucas/phpdotenv/pull/302)), moreover `Loader::load()` and its callers now return an associative array of the variables loaded with their values, rather than an array of raw lines from the environment file ([#306](https://github.com/vlucas/phpdotenv/pull/306)).

Release notes for 3.0.0 are available [here](https://github.com/vlucas/phpdotenv/releases/tag/v3.0.0).

### Details

V3 has changed the way you initialize the `Dotenv` class. Consequently, you will need to replace any occurrences of new Dotenv(...) with Dotenv::create(...), since our new native constructor takes a `Loader` instance now.

`Loader::load()` and its callers now return an associative array of the variables loaded with their values.

Value parsing has been modified in the following ways:

1. For unquoted strings, as soon as there's a hash, it's treated as a comment start.
2. We're being stricter about invalid escape sequences within quoted strings.
3. We're no longer trimming the parsed values of quoted strings.
4. Multiline quoted values are now permitted, and will be parsed by V3.

| input value | V2.5.2 | V2.6.1 | V3.3.1 |
|-|-|-|-|
| `foo#bar` | `foo#bar` | `foo#bar` | `foo` |
| `foo # bar` | `foo` | `foo` | `foo` |
| `"iiiiviiiixiiiiviiii\n"` | silent failure | `iiiviiiixiiiiviiii\n` | fails with invalid escape sequence exception |
| `"iiiiviiiixiiiiviiii\\n"` | `iiiiviiiixiiiiviiii\n` | `iiiiviiiixiiiiviiii\n` | `iiiiviiiixiiiiviiii\n` |
| `"foo\"bar"` | `foo"bar` | `foo"bar` | `foo"bar` |
| `"  foo "` | `foo` with whitespace trimmed | `foo` with whitespace trimmed | `foo` with 2 spaces in front and one after |

In double quoted strings, double quotes and backslashes need escaping with a backslash, and in single quoted strings, single quote and backslashes need escaping with a backslash. In v2.5.2, forgetting an escape can lead to odd results due to the regex running out of stack, but this was fixed in 2.6 and 3.3, with 2.6 allowing you to continue after an unescaped backslash, but 3.3 not.

It's possible to use phpdotenv V3 in a threaded environment, instructing it to not call any functions that are not tread-safe:

```php
<?php

use Dotenv\Dotenv;
use Dotenv\Environment\Adapter\EnvConstAdapter;
use Dotenv\Environment\Adapter\ServerConstAdapter;
use Dotenv\Environment\DotenvFactory;

$factory = new DotenvFactory([new EnvConstAdapter(), new ServerConstAdapter()]);

Dotenv::create($path, null, $factory)->load();
```

Finally, we note that the minimum supported version of PHP has increased from 5.3.9 to 5.4.0.
