<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="80px">
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
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/db-pgsql/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/db-pgsql/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/db-pgsql/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/db-pgsql/?branch=master)


## Support version

|  PHP | Pgsql Version            |  CI-Actions
|:----:|:------------------------:|:---:|
|**7.4 - 8.0**| **9.0 - 13.0**|[![Build status](https://github.com/yiisoft/db-pgsql/workflows/build/badge.svg)](https://github.com/yiisoft/db-pgsql/actions?query=workflow%3Abuild) [![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fdb-pgsql%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/db-pgsql/master) [![static analysis](https://github.com/yiisoft/db-pgsql/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/db-pgsql/actions?query=workflow%3A%22static+analysis%22) [![type-coverage](https://shepherd.dev/github/yiisoft/db-pgsql/coverage.svg)](https://shepherd.dev/github/yiisoft/db-pgsql)


## Installation

The package could be installed via composer:

```php
composer require yiisoft/db-pgsql
```

## Configuration

Using yiisoft/composer-config-plugin automatically get the settings of `Yiisoft\Cache\CacheInterface::class`, `LoggerInterface::class`, and `Profiler::class`.

Di-Container:

```php
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Pgsql\Connection as PgsqlConnection;

return [
    ConnectionInterface::class => [
        'class' => PgsqlConnection::class,
        '__construct()' => [
            'dsn' => $params['yiisoft/db-pgsql']['dsn']
        ],
        'setUsername()' => [$params['yiisoft/db-pgsql']['username']],
        'setPassword()' => [$params['yiisoft/db-pgsql']['password']],
    ]
];
```

Params.php

```php
use Yiisoft\Db\Connection\Dsn;

return [
    'yiisoft/db-pgsql' => [
        'dsn' => (new Dsn('pgsql', '127.0.0.1', 'yiitest', '5432'))->asString(),
        'username' => 'root',
        'password' => 'root',
    ]
];
```

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```shell
./vendor/bin/phpunit
```

### Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework. To run it:

```shell
./vendor/bin/infection
```

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

### Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

### Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)

## License

The Yii DataBase PostgreSQL Extension is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).
