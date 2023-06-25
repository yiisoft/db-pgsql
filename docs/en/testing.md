# Testing

## Github actions

All our packages have github actions by default, so you can test your [contribution](https://github.com/yiisoft/db-pgsql/blob/master/.github/CONTRIBUTING.md) in the cloud.

> Note: We recommend pull requesting in draft mode until all tests pass.

## Docker

For greater ease it is recommended to use docker containers. 

You can use the [docker-compose.yml](https://docs.docker.com/compose/compose-file/) file with PostgreSQL 15 
that is in the root of package:

```shell
docker compose up -d
```

or run container directly via command:

```shell
docker run --name pgsql -e POSTGRES_PASSWORD=root -e POSTGRES_USER=root -e POSTGRES_DB=yiitest -d postgres:15
```

If IP address at which the container with DB is accessible is different from `127.0.0.1`, then set environment variable
`YIISOFT_DB_PGSQL_TEST_HOST` to actual IP. For example:

```shell
export YIISOFT_DB_PGSQL_TEST_HOST=172.17.0.3
````

> Environment variable `YIISOFT_DB_PGSQL_TEST_HOST` usage only for this package testing. It's not need and not work for
> your application or library that used `yiisoft/db-pgsql`.

## Unit testing

The package is tested with [PHPUnit](https://phpunit.de/).

The following steps are required to run the tests:

1. Run the docker container with PostgreSQL database.
2. Install the dependencies of the project with composer.
3. Run the tests with the command:

```shell
vendor/bin/phpunit
```

### Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework with
[Infection Static Analysis Plugin](https://github.com/Roave/infection-static-analysis-plugin). To run it:

```shell
./vendor/bin/roave-infection-static-analysis-plugin
```

## Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

## Rector

Use [Rector](https://github.com/rectorphp/rector) to make codebase follow some specific rules or 
use either newest or any specific version of PHP: 

```shell
./vendor/bin/rector
```

## Composer require checker

This package uses [composer-require-checker](https://github.com/maglnet/ComposerRequireChecker) to check if all dependencies are correctly defined in `composer.json`.

To run the checker, execute the following command:

```shell
./vendor/bin/composer-require-checker
```
