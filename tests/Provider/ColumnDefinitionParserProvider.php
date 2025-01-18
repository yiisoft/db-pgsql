<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

class ColumnDefinitionParserProvider extends \Yiisoft\Db\Tests\Provider\ColumnDefinitionParserProvider
{
    public static function parse(): array
    {
        return [
            ...parent::parse(),
            ['double precision', ['type' => 'double precision']],
            ['character varying(126)', ['type' => 'character varying', 'size' => 126]],
            ['bit varying(8)', ['type' => 'bit varying', 'size' => 8]],
            ['timestamp without time zone', ['type' => 'timestamp without time zone']],
            ['timestamp with time zone', ['type' => 'timestamp with time zone']],
            ['time without time zone', ['type' => 'time without time zone']],
            ['time with time zone', ['type' => 'time with time zone']],
        ];
    }
}
