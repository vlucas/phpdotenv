# CONTRIBUTION GUIDELINES

Contributions are **welcome** and will be fully **credited**.

We accept contributions via pull requests on Github. Please review these guidelines before continuing.

## Guidelines

* Please follow the [PSR-12 Coding Style Guide](https://www.php-fig.org/psr/psr-12/).
* Ensure that the current tests pass, and if you've added something new, add the tests where relevant.
* Send a coherent commit history, making sure each individual commit in your pull request is meaningful.
* You may need to [rebase](https://git-scm.com/book/en/v2/Git-Branching-Rebasing) to avoid merge conflicts.
* If you are changing or adding to the behaviour or public api, you may need to update the docs.
* Please remember that we follow [SemVer](https://semver.org/).

## Running Tests

First, install the dependencies using [Composer](https://getcomposer.org/):

```bash
$ make install
```

Then run [PHPUnit](https://phpunit.de/) and the static analyzers:

```bash
$ make test
```

These will also be automatically run by [GitHub Actions](https://github.com/features/actions) against pull requests.
