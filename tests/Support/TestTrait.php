<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Support;

use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Tests\TestConnection;

use function preg_replace;
use function str_replace;

trait TestTrait
{
    public static function setUpBeforeClass(): void
    {
        $db = self::getDb();

        DbHelper::loadFixture($db, __DIR__ . '/Fixture/pgsql.sql');

        $db->close();
    }

    protected static function getDb(): Connection
    {
        return TestConnection::create();
    }

    protected static function replaceQuotes(string $sql): string
    {
        return str_replace(['\\[', '\\]'], ['[', ']'], preg_replace('/(\[\[)|((?<!(\[))]])/', '"', $sql));
    }
}
