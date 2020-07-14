PHP dotenv
==========

Loads environment variables from `.env` to `getenv()`, `$_ENV` and `$_SERVER` automagically.

![Banner](https://user-images.githubusercontent.com/2829600/71564012-31105580-2a91-11ea-9ad7-ef1278411b35.png)

<p align="center">
<a href="LICENSE"><img src="https://img.shields.io/badge/license-BSD%203--Clause-brightgreen.svg?style=flat-square" alt="Software License"></img></a>
<a href="https://packagist.org/packages/vlucas/phpdotenv"><img src="https://img.shields.io/packagist/dt/vlucas/phpdotenv.svg?style=flat-square" alt="Total Downloads"></img></a>
<a href="https://github.com/vlucas/phpdotenv/releases"><img src="https://img.shields.io/github/release/vlucas/phpdotenv.svg?style=flat-square" alt="Latest Version"></img></a>
</p>


Why .env?
---------

**You should never store sensitive credentials in your code**. Storing
[configuration in the environment](http://www.12factor.net/config) is one of
the tenets of a [twelve-factor app](http://www.12factor.net/). Anything that is
likely to change between deployment environments – such as database credentials
or credentials for 3rd party services – should be extracted from the code into
environment variables.

Basically, a `.env` file is an easy way to load custom configuration variables
that your application needs without having to modify .htaccess files or
Apache/nginx virtual hosts. This means you won't have to edit any files outside
the project, and all the environment variables are always set no matter how you
run your project - Apache, Nginx, CLI, and even PHP 5.4's built-in webserver.
It's WAY easier than all the other ways you know of to set environment
variables, and you're going to love it!

* NO editing virtual hosts in Apache or Nginx
* NO adding `php_value` flags to .htaccess files
* EASY portability and sharing of required ENV values
* COMPATIBLE with PHP's built-in web server and CLI runner

PHP dotenv is a PHP version of the original [Ruby
dotenv](https://github.com/bkeepers/dotenv).


Installation with Composer
--------------------------

Installation is super-easy via [Composer](https://getcomposer.org/):

```bash
$ composer require vlucas/phpdotenv
```

or add it by hand to your `composer.json` file.


UPGRADING FROM V3
-----------------

Version 4 sees some refactoring, and support for escaping dollars in values
(https://github.com/vlucas/phpdotenv/pull/380). It is no longer possible to
change immutability on the fly, and the `Loader` no longer is responsible for
tracking immutability. It is now the responsibility of "repositories" to track
this. One must explicitly decide if they want (im)mutability when constructing
an instance of `Dotenv\Dotenv`.

For more details, please see the
[release notes](https://github.com/vlucas/phpdotenv/releases/tag/v4.0.0) and
the [upgrading guide](UPGRADING.md).


UPGRADING FROM V2
-----------------

New in Version 3 is first-class support for multiline variables
([#301](https://github.com/vlucas/phpdotenv/pull/301)) and much more
flexibility in terms of which parts of the environment we try to read and
modify ([#300](https://github.com/vlucas/phpdotenv/pull/300)). Consequently,
you will need to replace any occurrences of `new Dotenv(...)` with
`Dotenv::create(...)`, since our new native constructor takes a `Loader`
instance now, so that it can be truly customized if required. Finally, one
should note that the loader will no longer be trimming values
([#302](https://github.com/vlucas/phpdotenv/pull/302)), moreover
`Loader::load()` and its callers now return an associative array of the
variables loaded with their values, rather than an array of raw lines from the
environment file ([#306](https://github.com/vlucas/phpdotenv/pull/306)).

For more details, please see the
[release notes](https://github.com/vlucas/phpdotenv/releases/tag/v3.0.0) and
the [upgrading guide](UPGRADING.md).


Usage
-----

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

Optionally you can pass in a filename as the second parameter, if you would like to use something other than `.env`

```php
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, 'myconfig');
$dotenv->load();
```

All of the defined variables are now accessible with the `getenv`
method, and are available in the `$_ENV` and `$_SERVER` super-globals.

```php
$s3_bucket = getenv('S3_BUCKET');
$s3_bucket = $_ENV['S3_BUCKET'];
$s3_bucket = $_SERVER['S3_BUCKET'];
```

You should also be able to access them using your framework's Request
class (if you are using a framework).

```php
$s3_bucket = $request->env('S3_BUCKET');
$s3_bucket = $request->getEnv('S3_BUCKET');
$s3_bucket = $request->server->get('S3_BUCKET');
$s3_bucket = env('S3_BUCKET');
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
$repository = Dotenv\Repository\RepositoryBuilder::create()
    ->withReaders([
        new Dotenv\Repository\Adapter\EnvConstAdapter(),
    ])
    ->withWriters([
        new Dotenv\Repository\Adapter\EnvConstAdapter(),
        new Dotenv\Repository\Adapter\PutenvAdapter(),
    ])
    ->immutable()
    ->make();

$dotenv = Dotenv\Dotenv::create($repository, __DIR__);
$dotenv->load();
```

The above example will write loaded values to `$_ENV` and `putenv`, but when
interpolating environment variables, we'll only read from `$_ENV`. Moreover, it
will never replace any variables already set before loading the file.


Requiring Variables to be Set
-----------------------------

Using Dotenv, you can require specific ENV vars to be defined ($_ENV, $_SERVER or getenv()) - throws an exception otherwise.
Note: It does not check for existence of a variable in a '.env' file. This is particularly useful to let people know any explicit required variables that your app will not work without.

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

You might also need to ensure that the variable is of an integer value. You may do the following:

```php
$dotenv->required('FOO')->isInteger();
```

If the environment variable is not an integer, you'd get an Exception:

```
One or more environment variables failed assertions: FOO is not an integer
```

### Boolean Variables

You may need to ensure a variable is in the form of a boolean, accepting "true", "false", "On", "1", "Yes", "Off", "0" and "No". You may do the following:

```php
$dotenv->required('FOO')->isBoolean();
```

If the environment variable is not a boolean, you'd get an Exception:

```
One or more environment variables failed assertions: FOO is not a boolean
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
One or more environment variables failed assertions: SESSION_STORE is not an
allowed value
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

Usage Notes
-----------

When a new developer clones your codebase, they will have an additional
**one-time step** to manually copy the `.env.example` file to `.env` and fill-in
their own values (or get any sensitive values from a project co-worker).


Security
--------

If you discover a security vulnerability within this package, please send an email to Graham Campbell at graham@alt-three.com. All security vulnerabilities will be promptly addressed. You may view our full security policy [here](https://github.com/vlucas/phpdotenv/security/policy).


License
-------

PHP dotenv is licensed under [The BSD 3-Clause License](LICENSE).


For Enterprise
--------------

Available as part of the Tidelift Subscription

The maintainers of `vlucas/phpdotenv` and thousands of other packages are working with Tidelift to deliver commercial support and maintenance for the open source dependencies you use to build your applications. Save time, reduce risk, and improve code health, while paying the maintainers of the exact dependencies you use. [Learn more.](https://tidelift.com/subscription/pkg/packagist-vlucas-phpdotenv?utm_source=packagist-vlucas-phpdotenv&utm_medium=referral&utm_campaign=enterprise&utm_term=repo)
