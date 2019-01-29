# Upgrading Guide

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

Finally, it's possible to use phpdotenv V3 in a threaded environment, instructing it to not call any functions that are not tread-safe:

```php
<?php

use Dotenv\Environment\Adapter\EnvConstAdapter;
use Dotenv\Environment\Adapter\ServerConstAdapter;
use Dotenv\Environment\DotenvFactory;
use Dotenv\Dotenv;

$factory = new DotenvFactory([new EnvConstAdapter(), new ServerConstAdapter()]);

Dotenv::create($path, null, $factory)->load();
```
