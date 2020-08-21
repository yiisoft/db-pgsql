<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="80px">
    </a>
    <a href="https://www.postgresql.org/" target="_blank">
        <img src="https://www.postgresql.org/media/img/about/press/elephant.png" height="80px">
    </a>
    <h1 align="center">Yii DataBase PostgreSQL Extension</h1>
    <br>
</p>

This package provides [PostgreSQL] extension for [Yii DataBase] library.
It is used in [Yii Framework] but is supposed to be usable separately.

[PostgreSQL]: https://www.postgresql.org/
[Yii DataBase]: https://github.com/yiisoft/db
[Yii Framework]: https://github.com/yiisoft/core

[![Latest Stable Version](https://poser.pugx.org/yiisoft/db-pgsql/v/stable.png)](https://packagist.org/packages/yiisoft/db-pgsql)
[![Total Downloads](https://poser.pugx.org/yiisoft/db-pgsql/downloads.png)](https://packagist.org/packages/yiisoft/db-pgsql)
[![Build status](https://github.com/yiisoft/db-pgsql/workflows/build/badge.svg)](https://github.com/yiisoft/db-pgsql/actions?query=workflow%3Abuild)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/db-pgsql/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/db-pgsql/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/db-pgsql/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/db-pgsql/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fdb-pgsql%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/db-pgsql/master)
[![static analysis](https://github.com/yiisoft/db-pgsql/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/db-pgsql/actions?query=workflow%3A%22static+analysis%22)


## Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```php
./vendor/bin/phpunit
```

Note: You must have postgreSQL installed to run the tests, it supports all postgreSQL versions.

## Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework. To run it:

```php
./vendor/bin/infection
```

## Static analysis

The code is statically analyzed with [Phan](https://github.com/phan/phan/wiki). To run static analysis:

```php
./vendor/bin/phan
```
