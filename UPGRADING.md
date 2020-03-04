# Upgrading Guide

## V4.0 to V4.1

There are no breaking changes in this release, but the `Dotenv\Dotenv` constructor now expects either an array of file paths as the third parameter, or an instance of `Dotenv\Store\StoreInterface`. Passing an array is deprecated, and will be removed in V5.

## V3 to V4

V4 has again changed the way you initialize the `Dotenv` class. If you want immutable loading of environment variables, then replace `Dotenv::create` with `Dotenv::createImmutable`, and if you want mutable loading, replace `Dotenv::create` with `Dotenv::createMutable` and `->overload()` with `->load()`. The `overload` method has been removed in faviour of specifying mutability at object construction.

The behaviour when parsing single quoted strings has now changed, to mimic the behaviour of bash. It is no longer possible to escape characters in single quoted strings, and everything is treated literally. As soon as the first single quote character is read, after the initial one, then the variable is treated as ending immediately at that point. When parsing unquoted or double quoted strings, it is now possible to escape dollar signs, to forcefully avoid variable interpolation. Escaping dollars is not mandated, in the sense that if a dollar is present, and not following by variable interpolation sytnax, this is allowed, and the dollar will be treated as a literal dollar. Finally, interpolation of variables is now performed right to left, instead of left to right, so it is possible to nest interpolations to allow using the value of a variable as the name of another for further interpolation.

The `getEnvironmentVariableNames` method is no longer available. This is because calls to `load()` (since v3.0.0) return an associative array of what was loaded, so `$dotenv->getEnvironmentVariableNames()` can be replaced with `array_keys($dotenv->load())`.

There have been various internal refactorings. Appart from what has already been mentioned, the only other changes likely to affect developers is:

1. The `Dotenv\Environment` namespace has been moved to `Dotenv\Repository`, the `Dotenv\Environment\Adapter\AdapterInterface` interface has been replaced by `Dotenv\Repository\Adapter\ReaderInterface` and `Dotenv\Repository\Adapter\WriterInterface`.
2. The `Dotenv\Environment\DotenvFactory` has been (roughly) replaced by `Dotenv\Repository\RepositoryBuilder`, and `Dotenv\Environment\FactoryInterface` has been deleted.
3. `Dotenv\Environment\AbstractVariables` has been replaced by `Dotenv\Repository\AbstractRepository`, `Dotenv\Environment\DotenvVariables` has been replaced by `Dotenv\Repository\AdapterRepository`, and `Dotenv\Environment\VariablesInterface` has been replaced by `Dotenv\Repository\RepositoryInterface`.
4. The `Dotenv\Loader` class has been moved to `Dotenv\Loader\Loader`, and now has a different public interface. It no longer expects any parameters at construction, and implements only the new interface `Dotenv\Loader\LoaderInterface`. Its reponsibility has changed to purely taking raw env file content, and handing it off to the parser, dealing with variable interpolation, and sending off instructions to the repository to set variables. No longer can it be used as a way to read the environment by callers, and nor does it track immutability.
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

Notice, that compared to v3, the loader no longer expects file paths in the constructor. Reading of the files is now managed by the `Dotenv\Dotenv` class. The loader is geuinely just loading the content into the repository.

Finally, we note that the minimum supported version of PHP has increased to 5.5.9, up from 5.4.0 in V3 and 5.3.9 in V2.

## V2 to V3

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
