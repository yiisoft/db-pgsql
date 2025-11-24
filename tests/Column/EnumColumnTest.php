<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Column;

use Yiisoft\Db\Pgsql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Common\CommonEnumColumnTest;

final class EnumColumnTest extends CommonEnumColumnTest
{
    use IntegrationTestTrait;

    protected function createDatabaseObjectsStatements(): array
    {
        return [
            <<<SQL
            CREATE TYPE enum_status AS ENUM ('active', 'unactive', 'pending')
            SQL,
            <<<SQL
            CREATE TABLE tbl_enum (
                id INTEGER,
                status enum_status
            )
            SQL,
        ];
    }

    protected function dropDatabaseObjectsStatements(): array
    {
        return [
            'DROP TABLE IF EXISTS tbl_enum',
            'DROP TYPE IF EXISTS enum_status',
        ];
    }
}
