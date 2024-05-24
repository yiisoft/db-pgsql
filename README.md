<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="80px" alt="Yii">
    </a>
    <a href="https://www.postgresql.org/" target="_blank">
        <img src="https://www.postgresql.org/media/img/about/press/elephant.png" height="80px" alt="PostgreSQL">
    </a>
    <h1 align="center">Yii Database PostgreSQL driver</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/db-pgsql/v/stable.png)](https://packagist.org/packages/yiisoft/db-pgsql)
[![Total Downloads](https://poser.pugx.org/yiisoft/db-pgsql/downloads.png)](https://packagist.org/packages/yiisoft/db-pgsql)
[![rector](https://github.com/yiisoft/db-pgsql/actions/workflows/rector.yml/badge.svg)](https://github.com/yiisoft/db-pgsql/actions/workflows/rector.yml)
[![codecov](https://codecov.io/gh/yiisoft/db-pgsql/branch/master/graph/badge.svg?token=3FGN91IVZA)](https://codecov.io/gh/yiisoft/db-pgsql)
[![StyleCI](https://github.styleci.io/repos/145220173/shield?branch=master)](https://github.styleci.io/repos/145220173?branch=master)

PostgreSQL driver for [Yii Database](https://github.com/yiisoft/db) is a [PostgreSQL] database adapter.
The package provides a database connection interface and a set of classes for interacting with a [PostgreSQL] database.
It allows you to perform common database operations such as executing queries, building and executing `INSERT`, `UPDATE`,
and `DELETE` statements, and working with transactions. It also provides support for PostgreSQL-specific features such
as stored procedures and server-side cursors.

[PostgreSQL]: https://www.postgresql.org/

## Support version

| PHP           | PostgreSQL Version  | CI-Actions
|---------------|---------------------|-----------
| **8.0 - 8.3** | **9.0 - 15.0** |[![build](https://github.com/yiisoft/db-pgsql/actions/workflows/build.yml/badge.svg?branch=dev)](https://github.com/yiisoft/db-pgsql/actions/workflows/build.yml) [![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fdb-pgsql%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/db-pgsql/master) [![static analysis](https://github.com/yiisoft/db-pgsql/actions/workflows/static.yml/badge.svg?branch=dev)](https://github.com/yiisoft/db-pgsql/actions/workflows/static.yml) [![type-coverage](https://shepherd.dev/github/yiisoft/db-pgsql/coverage.svg)](https://shepherd.dev/github/yiisoft/db-pgsql)

## Installation

The package could be installed with [Composer](https://getcomposer.org):

```shell
composer require yiisoft/db-pgsql
```

## Documentation

For config connection to PostgreSQL database check [Connecting PostgreSQL](https://github.com/yiisoft/db/blob/master/docs/guide/en/connection/pgsql.md).

[Check the documentation](https://github.com/yiisoft/db/blob/master/docs/guide/en/README.md) to learn about usage.

- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii Database PostgreSQL driver is free software. It is released under the terms of the BSD License.
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
