<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Support;

final class TestEnvironment
{
    public static function getPostgreSqlHost(): string
    {
        $host = getenv('YIISOFT_DB_PGSQL_TEST_HOST');
        return $host === false ? '127.0.0.1' : $host;
    }
}
