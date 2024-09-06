<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
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
            ['bool', ColumnType::BOOLEAN, BooleanColumnSchema::class],
            ['boolean', ColumnType::BOOLEAN, BooleanColumnSchema::class],
            ['bit', ColumnType::BIT, BitColumnSchema::class],
            ['bit varying', ColumnType::BIT, BitColumnSchema::class],
            ['smallint', ColumnType::SMALLINT, IntegerColumnSchema::class],
            ['smallserial', ColumnType::SMALLINT, IntegerColumnSchema::class],
            ['int2', ColumnType::SMALLINT, IntegerColumnSchema::class],
            ['serial2', ColumnType::SMALLINT, IntegerColumnSchema::class],
            ['int', ColumnType::INTEGER, IntegerColumnSchema::class],
            ['integer', ColumnType::INTEGER, IntegerColumnSchema::class],
            ['serial', ColumnType::INTEGER, IntegerColumnSchema::class],
            ['int4', ColumnType::INTEGER, IntegerColumnSchema::class],
            ['serial4', ColumnType::INTEGER, IntegerColumnSchema::class],
            ['bigint', ColumnType::BIGINT, IntegerColumnSchema::class],
            ['bigserial', ColumnType::BIGINT, IntegerColumnSchema::class],
            ['int8', ColumnType::BIGINT, IntegerColumnSchema::class],
            ['serial8', ColumnType::BIGINT, IntegerColumnSchema::class],
            ['oid', ColumnType::BIGINT, IntegerColumnSchema::class],
            ['pg_lsn', ColumnType::BIGINT, IntegerColumnSchema::class],
            ['real', ColumnType::FLOAT, DoubleColumnSchema::class],
            ['float4', ColumnType::FLOAT, DoubleColumnSchema::class],
            ['float8', ColumnType::DOUBLE, DoubleColumnSchema::class],
            ['double precision', ColumnType::DOUBLE, DoubleColumnSchema::class],
            ['decimal', ColumnType::DECIMAL, DoubleColumnSchema::class],
            ['numeric', ColumnType::DECIMAL, DoubleColumnSchema::class],
            ['money', ColumnType::MONEY, StringColumnSchema::class],
            ['char', ColumnType::CHAR, StringColumnSchema::class],
            ['character', ColumnType::CHAR, StringColumnSchema::class],
            ['bpchar', ColumnType::CHAR, StringColumnSchema::class],
            ['character varying', ColumnType::STRING, StringColumnSchema::class],
            ['varchar', ColumnType::STRING, StringColumnSchema::class],
            ['text', ColumnType::TEXT, StringColumnSchema::class],
            ['bytea', ColumnType::BINARY, BinaryColumnSchema::class],
            ['date', ColumnType::DATE, StringColumnSchema::class],
            ['time', ColumnType::TIME, StringColumnSchema::class],
            ['time without time zone', ColumnType::TIME, StringColumnSchema::class],
            ['time with time zone', ColumnType::TIME, StringColumnSchema::class],
            ['timetz', ColumnType::TIME, StringColumnSchema::class],
            ['timestamp', ColumnType::TIMESTAMP, StringColumnSchema::class],
            ['timestamp without time zone', ColumnType::TIMESTAMP, StringColumnSchema::class],
            ['timestamp with time zone', ColumnType::TIMESTAMP, StringColumnSchema::class],
            ['timestamptz', ColumnType::TIMESTAMP, StringColumnSchema::class],
            ['abstime', ColumnType::TIMESTAMP, StringColumnSchema::class],
            ['interval', ColumnType::STRING, StringColumnSchema::class],
            ['box', ColumnType::STRING, StringColumnSchema::class],
            ['circle', ColumnType::STRING, StringColumnSchema::class],
            ['point', ColumnType::STRING, StringColumnSchema::class],
            ['line', ColumnType::STRING, StringColumnSchema::class],
            ['lseg', ColumnType::STRING, StringColumnSchema::class],
            ['polygon', ColumnType::STRING, StringColumnSchema::class],
            ['path', ColumnType::STRING, StringColumnSchema::class],
            ['cidr', ColumnType::STRING, StringColumnSchema::class],
            ['inet', ColumnType::STRING, StringColumnSchema::class],
            ['macaddr', ColumnType::STRING, StringColumnSchema::class],
            ['tsquery', ColumnType::STRING, StringColumnSchema::class],
            ['tsvector', ColumnType::STRING, StringColumnSchema::class],
            ['txid_snapshot', ColumnType::STRING, StringColumnSchema::class],
            ['unknown', ColumnType::STRING, StringColumnSchema::class],
            ['uuid', ColumnType::STRING, StringColumnSchema::class],
            ['xml', ColumnType::STRING, StringColumnSchema::class],
            ['json', ColumnType::JSON, JsonColumnSchema::class],
            ['jsonb', ColumnType::JSON, JsonColumnSchema::class],
        ];
    }

    public static function definitions(): array
    {
        $definitions = parent::definitions();

        unset($definitions['bigint UNSIGNED']);

        return $definitions;
    }
}
