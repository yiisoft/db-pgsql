<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="80px" alt="Yii">
    </a>
    <a href="https://www.postgresql.org/" target="_blank">
        <img src="https://www.postgresql.org/media/img/about/press/elephant.png" height="80px" alt="PostgreSQL">
    </a>
    <h1 align="center">Yii Database PostgreSQL Driver</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/db-pgsql/v)](https://packagist.org/packages/yiisoft/db-pgsql)
[![Total Downloads](https://poser.pugx.org/yiisoft/db-pgsql/downloads)](https://packagist.org/packages/yiisoft/db-pgsql)
[![Build status](https://github.com/yiisoft/db-pgsql/actions/workflows/build.yml/badge.svg)](https://github.com/yiisoft/db-pgsql/actions/workflows/build.yml)
[![Code Coverage](https://codecov.io/gh/yiisoft/db-pgsql/branch/master/graph/badge.svg?token=UF9VERNMYU)](https://codecov.io/gh/yiisoft/db-pgsql)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fdb-pgsql%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/db-pgsql/master)
[![static analysis](https://github.com/yiisoft/db-pgsql/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/db-pgsql/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/db-pgsql/coverage.svg)](https://shepherd.dev/github/yiisoft/db-pgsql)
[![psalm-level](https://shepherd.dev/github/yiisoft/db-pgsql/level.svg)](https://shepherd.dev/github/yiisoft/db-pgsql)

PostgreSQL driver for [Yii Database](https://github.com/yiisoft/db) is a package for working with
[PostgreSQL](https://www.postgresql.org/) databases in PHP. It includes a database connection class, a command
builder class, and a set of classes for representing database tables and rows as PHP objects.

Driver supports PostgreSQL 9 or higher.

## Requirements

- PHP 8.1 - 8.4.
- `pdo_pgsql` PHP extension.

## Installation

The package could be installed with [Composer](https://getcomposer.org):

```shell
composer require yiisoft/db-pgsql
```

> [!IMPORTANT]
> See also [installation notes](https://github.com/yiisoft/db/?tab=readme-ov-file#installation) for `yiisoft/db`
> package.

## Documentation

For config connection to PostgreSQL database check
[Connecting PostgreSQL](https://github.com/yiisoft/db/blob/master/docs/guide/en/connection/pgsql.md).

Check the `yiisoft/db` [docs](https://github.com/yiisoft/db/blob/master/docs/guide/en/README.md) to learn about usage.

- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii Database PostgreSQL Driver is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
