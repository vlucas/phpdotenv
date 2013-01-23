PHP dotenv
==========

Loads environment variables from `.env` to `getenv()`, `$_ENV` and
`$_SERVER` automagically.

This is a PHP version of the original [Ruby
dotenv](https://github.com/bkeepers/dotenv).

Why .env?
---------
Basically, a `.env` file is an easy way to load custom environment
variables that your application needs without having to modify .htaccess
files or Apache/nginx virtual hosts. The `.env` file is generally kept
out of version control since it can contain sensitive API keys and
passwords. A separate `.env.example` file is created with all
the required environment variables defined except for the sensitive
ones, which are either user-supplied for their own development
environments or are communicated elsewhere to project collaborators. The
project collaborators then independently copy the `.env.example` file to
a local `.env` and ensure all the settings are correct for their local
environment, filling in the secret keys or providing their own values when
necessary.

Installation with Composer
--------------------------

```shell
curl -s http://getcomposer.org/installer | php
php composer.phar require vlucas/phpdotenv *
```

Usage
-----
Add your application configuration to a `.env` file in the root of your
project.

```shell
S3_BUCKET=dotenv
SECRET_KEY=souper_seekret_key
```

You can also create files per environment, such as `.env.test`:

```shell
S3_BUCKET=test
SECRET_KEY=test
```

You can then load `.env` in your application with a single line:
```php
Dotenv::load(__DIR__);
```

Or you can load a specific file such as `.env.test`
```php
Dotenv::load(__DIR__, '.env.test');
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
```

Contributing
------------

1. Fork it
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Make your changes
4. Run the tests, adding new ones for your own code if necessary (`phpunit`)
5. Commit your changes (`git commit -am 'Added some feature'`)
6. Push to the branch (`git push origin my-new-feature`)
7. Create new Pull Request

