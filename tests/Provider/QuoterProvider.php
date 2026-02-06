<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

final class QuoterProvider extends \Yiisoft\Db\Tests\Provider\QuoterProvider
{
    public static function columnNames(): array
    {
        return [
            ...parent::columnNames(),
            ['array_col[1]', '"array_col"[1]'],
            ['multi_array_col[1][2]', '"multi_array_col"[1][2]'],
            ['table_name.array_col[1]', '"table_name"."array_col"[1]'],
            ['[[array_col]][1]', '[[array_col]][1]'],
            ['(array_col[1])', '(array_col[1])'],
        ];
    }

    public static function tableNameParts(): array
    {
        return [
            ['', ['name' => '']],
            ['""', ['name' => '']],
            ['animal', ['name' => 'animal']],
            ['"animal"', ['name' => 'animal']],
            ['dbo.animal', ['schemaName' => 'dbo', 'name' => 'animal']],
            ['"dbo"."animal"', ['schemaName' => 'dbo', 'name' => 'animal']],
            ['"dbo".animal', ['schemaName' => 'dbo', 'name' => 'animal']],
            ['dbo."animal"', ['schemaName' => 'dbo', 'name' => 'animal']],
        ];
    }
}
