PHP dotenv
==========

Loads environment variables from `.env` to `getenv()`, `$_ENV` and `$_SERVER` automagically.

![Banner](https://user-images.githubusercontent.com/2829600/71564012-31105580-2a91-11ea-9ad7-ef1278411b35.png)

<p align="center">
<a href="LICENSE"><img src="https://img.shields.io/badge/license-BSD%203--Clause-brightgreen.svg?style=flat-square" alt="Software License"></img></a>
<a href="https://packagist.org/packages/vlucas/phpdotenv"><img src="https://img.shields.io/packagist/dt/vlucas/phpdotenv.svg?style=flat-square" alt="Total Downloads"></img></a>
<a href="https://github.com/vlucas/phpdotenv/releases"><img src="https://img.shields.io/github/release/vlucas/phpdotenv.svg?style=flat-square" alt="Latest Version"></img></a>
</p>

<div align="center">
 
**Special thanks to [our sponsors](https://github.com/sponsors/GrahamCampbell)**
 
<br>
<a href="https://www.dotenvx.com/?utm_source=github&utm_medium=referral&utm_campaign=phpdotenv">
  <div>
    <img src="https://dotenvx.com/logo.png" width="120" alt="Dotenvx">
  </div>
  <b>Need to sync .env files across teams and environments?</b>
  <div>
    <sup>Dotenvx builds on the simplicity of phpdotenv with support for encryption, multiple environments, and team workflows. Use it alongside phpdotenv to modernize your secrets management.</sup>
  </div>
</a>
 
<hr>
</div>


## Why .env?

**You should never store sensitive credentials in your code**. Storing
[configuration in the environment](https://www.12factor.net/config) is one of
the tenets of a [twelve-factor app](https://www.12factor.net/). Anything that
is likely to change between deployment environments – such as database
credentials or credentials for 3rd party services – should be extracted from
the code into environment variables.

Basically, a `.env` file is an easy way to load custom configuration variables
that your application needs without having to modify .htaccess files or
Apache/nginx virtual hosts. This means you won't have to edit any files outside
the project, and all the environment variables are always set no matter how you
run your project - Apache, Nginx, CLI, and even PHP's built-in webserver. It's
WAY easier than all the other ways you know of to set environment variables,
and you're going to love it!

* NO editing virtual hosts in Apache or Nginx
* NO adding `php_value` flags to .htaccess files
* EASY portability and sharing of required ENV values
* COMPATIBLE with PHP's built-in web server and CLI runner

PHP dotenv is a PHP version of the original [Ruby
dotenv](https://github.com/bkeepers/dotenv).


## Installation

Installation is super-easy via [Composer](https://getcomposer.org/):

```bash
$ composer require vlucas/phpdotenv
```

or add it by hand to your `composer.json` file.


## Upgrading

We follow [semantic versioning](https://semver.org/), which means breaking
changes may occur between major releases. We have upgrading guides available
for V2 to V3, V3 to V4 and V4 to V5 available [here](UPGRADING.md).


## Usage

The `.env` file is generally kept out of version control since it can contain
sensitive API keys and passwords. A separate `.env.example` file is created
with all the required environment variables defined except for the sensitive
ones, which are either user-supplied for their own development environments or
are communicated elsewhere to project collaborators. The project collaborators
then independently copy the `.env.example` file to a local `.env` and ensure
all the settings are correct for their local environment, filling in the secret
keys or providing their own values when necessary. In this usage, the `.env`
file should be added to the project's `.gitignore` file so that it will never
be committed by collaborators.  This usage ensures that no sensitive passwords
or API keys will ever be in the version control history so there is less risk
of a security breach, and production values will never have to be shared with
all project collaborators.

Add your application configuration to a `.env` file in the root of your
project. **Make sure the `.env` file is added to your `.gitignore` so it is not
checked-in the code**

```shell
S3_BUCKET="dotenv"
SECRET_KEY="souper_seekret_key"
```

Now create a file named `.env.example` and check this into the project. This
should have the ENV variables you need to have set, but the values should
either be blank or filled with dummy data. The idea is to let people know what
variables are required, but not give them the sensitive production values.

```shell
S3_BUCKET="devbucket"
SECRET_KEY="abc123"
```

You can then load `.env` in your application with:

```php
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
```

To suppress the exception that is thrown when there is no `.env` file, you can:

```php
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();
```

Optionally you can pass in a filename as the second parameter, if you would
like to use something other than `.env`:

```php
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, 'myconfig');
$dotenv->load();
```

All of the defined variables are now available in the `$_ENV` and `$_SERVER`
super-globals.

```php
$s3_bucket = $_ENV['S3_BUCKET'];
$s3_bucket = $_SERVER['S3_BUCKET'];
```


### Putenv and Getenv

Using `getenv()` and `putenv()` is strongly discouraged due to the fact that
these functions are not thread safe, however it is still possible to instruct
PHP dotenv to use these functions. Instead of calling
`Dotenv::createImmutable`, one can call `Dotenv::createUnsafeImmutable`, which
will add the `PutenvAdapter` behind the scenes. Your environment variables will
now be available using the `getenv` method, as well as the super-globals:

```php
$s3_bucket = getenv('S3_BUCKET');
$s3_bucket = $_ENV['S3_BUCKET'];
$s3_bucket = $_SERVER['S3_BUCKET'];
```


### Nesting Variables

It's possible to nest an environment variable within another, useful to cut
down on repetition.

This is done by wrapping an existing environment variable in `${…}` e.g.

```shell
BASE_DIR="/var/webroot/project-root"
CACHE_DIR="${BASE_DIR}/cache"
TMP_DIR="${BASE_DIR}/tmp"
```


### Immutability and Repository Customization

Immutability refers to if Dotenv is allowed to overwrite existing environment
variables. If you want Dotenv to overwrite existing environment variables,
use `createMutable` instead of `createImmutable`:

```php
$dotenv = Dotenv\Dotenv::createMutable(__DIR__);
$dotenv->load();
```

Behind the scenes, this is instructing the "repository" to allow immutability
or not. By default, the repository is configured to allow overwriting existing
values by default, which is relevant if one is calling the "create" method
using the `RepositoryBuilder` to construct a more custom repository:

```php
$repository = Dotenv\Repository\RepositoryBuilder::createWithNoAdapters()
    ->addAdapter(Dotenv\Repository\Adapter\EnvConstAdapter::class)
    ->addWriter(Dotenv\Repository\Adapter\PutenvAdapter::class)
    ->immutable()
    ->make();

$dotenv = Dotenv\Dotenv::create($repository, __DIR__);
$dotenv->load();
```

The above example will write loaded values to `$_ENV` and `putenv`, but when
interpolating environment variables, we'll only read from `$_ENV`. Moreover, it
will never replace any variables already set before loading the file.

By means of another example, one can also specify a set of variables to be
allow listed. That is, only the variables in the allow list will be loaded:

```php
$repository = Dotenv\Repository\RepositoryBuilder::createWithDefaultAdapters()
    ->allowList(['FOO', 'BAR'])
    ->make();

$dotenv = Dotenv\Dotenv::create($repository, __DIR__);
$dotenv->load();
```


### Requiring Variables to be Set

PHP dotenv has built in validation functionality, including for enforcing the
presence of an environment variable. This is particularly useful to let people
know any explicit required variables that your app will not work without.

You can use a single string:

```php
$dotenv->required('DATABASE_DSN');
```

Or an array of strings:

```php
$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']);
```

If any ENV vars are missing, Dotenv will throw a `RuntimeException` like this:

```
One or more environment variables failed assertions: DATABASE_DSN is missing
```


### Empty Variables

Beyond simply requiring a variable to be set, you might also need to ensure the
variable is not empty:

```php
$dotenv->required('DATABASE_DSN')->notEmpty();
```

If the environment variable is empty, you'd get an Exception:

```
One or more environment variables failed assertions: DATABASE_DSN is empty
```


### Integer Variables

You might also need to ensure that the variable is of an integer value. You may
do the following:

```php
$dotenv->required('FOO')->isInteger();
```

If the environment variable is not an integer, you'd get an Exception:

```
One or more environment variables failed assertions: FOO is not an integer.
```

One may only want to enforce validation rules when a variable is set. We
support this too:

```php
$dotenv->ifPresent('FOO')->isInteger();
```


### Boolean Variables

You may need to ensure a variable is in the form of a boolean, accepting
"true", "false", "On", "1", "Yes", "Off", "0" and "No". You may do the
following:

```php
$dotenv->required('FOO')->isBoolean();
```

If the environment variable is not a boolean, you'd get an Exception:

```
One or more environment variables failed assertions: FOO is not a boolean.
```

Similarly, one may write:

```php
$dotenv->ifPresent('FOO')->isBoolean();
```


### Allowed Values

It is also possible to define a set of values that your environment variable
should be. This is especially useful in situations where only a handful of
options or drivers are actually supported by your code:

```php
$dotenv->required('SESSION_STORE')->allowedValues(['Filesystem', 'Memcached']);
```

If the environment variable wasn't in this list of allowed values, you'd get a
similar Exception:

```
One or more environment variables failed assertions: SESSION_STORE is not an allowed value.
```

It is also possible to define a regex that your environment variable should be.
```php
$dotenv->required('FOO')->allowedRegexValues('([[:lower:]]{3})');
```


### Comments

You can comment your `.env` file using the `#` character. E.g.

```shell
# this is a comment
VAR="value" # comment
VAR=value # comment
```


### Parsing Without Loading

Sometimes you just wanna parse the file and resolve the nested environment variables, by giving us a string, and have an array returned back to you. While this is already possible, it is a little fiddly, so we have provided a direct way to do this:

```php
// ['FOO' => 'Bar', 'BAZ' => 'Hello Bar']
Dotenv\Dotenv::parse("FOO=Bar\nBAZ=\"Hello \${FOO}\"");
```

This is exactly the same as:

```php
Dotenv\Dotenv::createArrayBacked(__DIR__)->load();
```

only, instead of providing the directory to find the file, you have directly provided the file contents.


### Usage Notes

When a new developer clones your codebase, they will have an additional
one-time step to manually copy the `.env.example` file to `.env` and fill-in
their own values (or get any sensitive values from a project co-worker).


### Troubleshooting

In certain server setups (most commonly found in shared hosting), PHP might deactivate superglobals like `$_ENV` or `$_SERVER`. If these variables are not set, review the `variables_order` in the `php.ini` file. See [php.net/manual/en/ini.core.php#ini.variables-order](https://www.php.net/manual/en/ini.core.php#ini.variables-order).

## Security

If you discover a security vulnerability within this package, please send an email to security@tidelift.com. All security vulnerabilities will be promptly addressed. You may view our full security policy [here](https://github.com/vlucas/phpdotenv/security/policy).


## License

PHP dotenv is licensed under [The BSD 3-Clause License](LICENSE).


## For Enterprise

Available as part of the Tidelift Subscription

The maintainers of `vlucas/phpdotenv` and thousands of other packages are working with Tidelift to deliver commercial support and maintenance for the open source dependencies you use to build your applications. Save time, reduce risk, and improve code health, while paying the maintainers of the exact dependencies you use. [Learn more.](https://tidelift.com/subscription/pkg/packagist-vlucas-phpdotenv?utm_source=packagist-vlucas-phpdotenv&utm_medium=referral&utm_campaign=enterprise&utm_term=repo)
