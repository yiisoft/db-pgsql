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

Di-Container:

```php
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Helper\Dsn;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Log\Logger;
use Yiisoft\Log\Target\File\FileRotator;
use Yiisoft\Log\Target\File\FileRotatorInterface;
use Yiisoft\Log\Target\File\FileTarget;
use Yiisoft\Profiler\Profiler;

return [
    ContainerInterface::class => static function (ContainerInterface $container) {
        return $container;
    },

    Aliases::class => [
        '@root' => dirname(__DIR__, 1), // directory / packages.
        '@runtime' => '@root/runtime' 
    ],

    CacheInterface::class => static function () {
        return new Cache(new ArrayCache());
    },

    FileRotatorInterface::class => static function () {
        return new FileRotator(10);
    },

    LoggerInterface::class => static function (ContainerInterface $container) {
        $aliases = $container->get(Aliases::class);
        $fileRotator = $container->get(FileRotatorInterface::class);

        $fileTarget = new FileTarget(
            $aliases->get('@runtime/logs/app.log'),
            $fileRotator
        );

        $fileTarget->setLevels(
            [
                LogLevel::EMERGENCY,
                LogLevel::ERROR,
                LogLevel::WARNING,
                LogLevel::INFO,
                LogLevel::DEBUG
            ]
        );

        return new Logger(['file' => $fileTarget]);
    },

    Profiler::class => static function (ContainerInterface $container) {
        return new Profiler($container->get(LoggerInterface::class));
    },

    Dsn::class => static function () use ($params) {
        return new Dsn(
            $params['yiisoft/db-pgsql']['dsn']['driver'],
            $params['yiisoft/db-pgsql']['dsn']['host'],
            $params['yiisoft/db-pgsql']['dsn']['dbname'],
            $params['yiisoft/db-pgsql']['dsn']['port'],
        );
    },

    ConnectionInterface::class  => static function (ContainerInterface $container) use ($params) {
        $connection = new Connection(
            $container->get(CacheInterface::class),
            $container->get(LoggerInterface::class),
            $container->get(Profiler::class),
            $container->get(Dsn::class)->getDsn(),
        );

        $connection->setUsername($params['yiisoft/db-pgsql']['username']);
        $connection->setPassword($params['yiisoft/db-pgsql']['password']);

        return $connection;
    }
];
```

Params.php

```php
return [
    'yiisoft/db-pgsql' => [
        'dsn' => [
            'driver' => 'pgsql',
            'host' => '127.0.0.1',
            'dbname' => 'yiitest',
            'port' => '5432'
        ],
        'username' => 'root',
        'password' => 'root',
    ]
];
```

## Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```php
./vendor/bin/phpunit
```

Note: You must have PGSQL installed to run the tests, it supports all PGSQL versions.

## Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework. To run it:

```php
./vendor/bin/infection
```

## Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/docs/). To run static analysis:

```php
./vendor/bin/psalm
```
