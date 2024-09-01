<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

use Yiisoft\Db\Pgsql\Column\BinaryColumnSchema;
use Yiisoft\Db\Pgsql\Column\BitColumnSchema;
use Yiisoft\Db\Pgsql\Column\BooleanColumnSchema;
use Yiisoft\Db\Pgsql\Column\IntegerColumnSchema;
use Yiisoft\Db\Schema\Column\DoubleColumnSchema;
use Yiisoft\Db\Schema\Column\JsonColumnSchema;
use Yiisoft\Db\Schema\Column\StringColumnSchema;

final class ColumnFactoryProvider extends \Yiisoft\Db\Tests\Provider\ColumnFactoryProvider
{
    public static function dbTypes(): array
    {
        return [
            // db type, expected abstract type, expected instance of
            ['bool', 'boolean', BooleanColumnSchema::class],
            ['boolean', 'boolean', BooleanColumnSchema::class],
            ['bit', 'bit', BitColumnSchema::class],
            ['bit varying', 'bit', BitColumnSchema::class],
            ['smallint', 'smallint', IntegerColumnSchema::class],
            ['smallserial', 'smallint', IntegerColumnSchema::class],
            ['int2', 'smallint', IntegerColumnSchema::class],
            ['serial2', 'smallint', IntegerColumnSchema::class],
            ['int', 'integer', IntegerColumnSchema::class],
            ['integer', 'integer', IntegerColumnSchema::class],
            ['serial', 'integer', IntegerColumnSchema::class],
            ['int4', 'integer', IntegerColumnSchema::class],
            ['serial4', 'integer', IntegerColumnSchema::class],
            ['bigint', 'bigint', IntegerColumnSchema::class],
            ['bigserial', 'bigint', IntegerColumnSchema::class],
            ['int8', 'bigint', IntegerColumnSchema::class],
            ['serial8', 'bigint', IntegerColumnSchema::class],
            ['oid', 'bigint', IntegerColumnSchema::class],
            ['pg_lsn', 'bigint', IntegerColumnSchema::class],
            ['real', 'float', DoubleColumnSchema::class],
            ['float4', 'float', DoubleColumnSchema::class],
            ['float8', 'double', DoubleColumnSchema::class],
            ['double precision', 'double', DoubleColumnSchema::class],
            ['decimal', 'decimal', DoubleColumnSchema::class],
            ['numeric', 'decimal', DoubleColumnSchema::class],
            ['money', 'money', StringColumnSchema::class],
            ['char', 'char', StringColumnSchema::class],
            ['character', 'char', StringColumnSchema::class],
            ['bpchar', 'char', StringColumnSchema::class],
            ['character varying', 'string', StringColumnSchema::class],
            ['varchar', 'string', StringColumnSchema::class],
            ['text', 'text', StringColumnSchema::class],
            ['bytea', 'binary', BinaryColumnSchema::class],
            ['date', 'date', StringColumnSchema::class],
            ['time', 'time', StringColumnSchema::class],
            ['time without time zone', 'time', StringColumnSchema::class],
            ['time with time zone', 'time', StringColumnSchema::class],
            ['timetz', 'time', StringColumnSchema::class],
            ['timestamp', 'timestamp', StringColumnSchema::class],
            ['timestamp without time zone', 'timestamp', StringColumnSchema::class],
            ['timestamp with time zone', 'timestamp', StringColumnSchema::class],
            ['timestamptz', 'timestamp', StringColumnSchema::class],
            ['abstime', 'timestamp', StringColumnSchema::class],
            ['interval', 'string', StringColumnSchema::class],
            ['box', 'string', StringColumnSchema::class],
            ['circle', 'string', StringColumnSchema::class],
            ['point', 'string', StringColumnSchema::class],
            ['line', 'string', StringColumnSchema::class],
            ['lseg', 'string', StringColumnSchema::class],
            ['polygon', 'string', StringColumnSchema::class],
            ['path', 'string', StringColumnSchema::class],
            ['cidr', 'string', StringColumnSchema::class],
            ['inet', 'string', StringColumnSchema::class],
            ['macaddr', 'string', StringColumnSchema::class],
            ['tsquery', 'string', StringColumnSchema::class],
            ['tsvector', 'string', StringColumnSchema::class],
            ['txid_snapshot', 'string', StringColumnSchema::class],
            ['unknown', 'string', StringColumnSchema::class],
            ['uuid', 'string', StringColumnSchema::class],
            ['xml', 'string', StringColumnSchema::class],
            ['json', 'json', JsonColumnSchema::class],
            ['jsonb', 'json', JsonColumnSchema::class],
        ];
    }

    public static function definitions(): array
    {
        $definitions = parent::definitions();

        unset($definitions['bigint UNSIGNED']);

        return $definitions;
    }
}
