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
            ['timestamp(3) with time zone', ['type' => 'timestamp(3) with time zone', 'size' => 3]],
            ['time without time zone', ['type' => 'time without time zone']],
            ['time (3) with time zone', ['type' => 'time (3) with time zone', 'size' => 3]],
        ];
    }
}
