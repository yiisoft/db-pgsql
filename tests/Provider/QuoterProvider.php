<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

final class QuoterProvider extends \Yiisoft\Db\Tests\Provider\QuoterProvider
{
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
