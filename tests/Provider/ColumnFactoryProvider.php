<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Pgsql\Column\ArrayColumn;
use Yiisoft\Db\Pgsql\Column\BinaryColumn;
use Yiisoft\Db\Pgsql\Column\BitColumn;
use Yiisoft\Db\Pgsql\Column\BooleanColumn;
use Yiisoft\Db\Pgsql\Column\IntegerColumn;
use Yiisoft\Db\Pgsql\Column\StructuredColumn;
use Yiisoft\Db\Schema\Column\DateTimeColumn;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\JsonColumn;
use Yiisoft\Db\Schema\Column\StringColumn;

final class ColumnFactoryProvider extends \Yiisoft\Db\Tests\Provider\ColumnFactoryProvider
{
    public static function dbTypes(): array
    {
        return [
            // db type, expected abstract type, expected instance of
            ['bool', ColumnType::BOOLEAN, BooleanColumn::class],
            ['boolean', ColumnType::BOOLEAN, BooleanColumn::class],
            ['bit', ColumnType::BIT, BitColumn::class],
            ['bit varying', ColumnType::BIT, BitColumn::class],
            ['smallint', ColumnType::SMALLINT, IntegerColumn::class],
            ['smallserial', ColumnType::SMALLINT, IntegerColumn::class],
            ['int2', ColumnType::SMALLINT, IntegerColumn::class],
            ['serial2', ColumnType::SMALLINT, IntegerColumn::class],
            ['int', ColumnType::INTEGER, IntegerColumn::class],
            ['integer', ColumnType::INTEGER, IntegerColumn::class],
            ['serial', ColumnType::INTEGER, IntegerColumn::class],
            ['int4', ColumnType::INTEGER, IntegerColumn::class],
            ['serial4', ColumnType::INTEGER, IntegerColumn::class],
            ['bigint', ColumnType::BIGINT, IntegerColumn::class],
            ['bigserial', ColumnType::BIGINT, IntegerColumn::class],
            ['int8', ColumnType::BIGINT, IntegerColumn::class],
            ['serial8', ColumnType::BIGINT, IntegerColumn::class],
            ['oid', ColumnType::BIGINT, IntegerColumn::class],
            ['pg_lsn', ColumnType::BIGINT, IntegerColumn::class],
            ['real', ColumnType::FLOAT, DoubleColumn::class],
            ['float4', ColumnType::FLOAT, DoubleColumn::class],
            ['float8', ColumnType::DOUBLE, DoubleColumn::class],
            ['double precision', ColumnType::DOUBLE, DoubleColumn::class],
            ['decimal', ColumnType::DECIMAL, DoubleColumn::class],
            ['numeric', ColumnType::DECIMAL, DoubleColumn::class],
            ['money', ColumnType::MONEY, StringColumn::class],
            ['char', ColumnType::CHAR, StringColumn::class],
            ['character', ColumnType::CHAR, StringColumn::class],
            ['bpchar', ColumnType::CHAR, StringColumn::class],
            ['character varying', ColumnType::STRING, StringColumn::class],
            ['varchar', ColumnType::STRING, StringColumn::class],
            ['text', ColumnType::TEXT, StringColumn::class],
            ['bytea', ColumnType::BINARY, BinaryColumn::class],
            ['abstime', ColumnType::DATETIME, DateTimeColumn::class],
            ['timestamp', ColumnType::DATETIME, DateTimeColumn::class],
            ['timestamp without time zone', ColumnType::DATETIME, DateTimeColumn::class],
            ['timestamp with time zone', ColumnType::DATETIMETZ, DateTimeColumn::class],
            ['timestamptz', ColumnType::DATETIMETZ, DateTimeColumn::class],
            ['time', ColumnType::TIME, DateTimeColumn::class],
            ['time without time zone', ColumnType::TIME, DateTimeColumn::class],
            ['time with time zone', ColumnType::TIMETZ, DateTimeColumn::class],
            ['timetz', ColumnType::TIMETZ, DateTimeColumn::class],
            ['date', ColumnType::DATE, DateTimeColumn::class],
            ['interval', ColumnType::STRING, StringColumn::class],
            ['box', ColumnType::STRING, StringColumn::class],
            ['circle', ColumnType::STRING, StringColumn::class],
            ['point', ColumnType::STRING, StringColumn::class],
            ['line', ColumnType::STRING, StringColumn::class],
            ['lseg', ColumnType::STRING, StringColumn::class],
            ['polygon', ColumnType::STRING, StringColumn::class],
            ['path', ColumnType::STRING, StringColumn::class],
            ['cidr', ColumnType::STRING, StringColumn::class],
            ['inet', ColumnType::STRING, StringColumn::class],
            ['macaddr', ColumnType::STRING, StringColumn::class],
            ['tsquery', ColumnType::STRING, StringColumn::class],
            ['tsvector', ColumnType::STRING, StringColumn::class],
            ['txid_snapshot', ColumnType::STRING, StringColumn::class],
            ['unknown', ColumnType::STRING, StringColumn::class],
            ['uuid', ColumnType::STRING, StringColumn::class],
            ['xml', ColumnType::STRING, StringColumn::class],
            ['json', ColumnType::JSON, JsonColumn::class],
            ['jsonb', ColumnType::JSON, JsonColumn::class],
        ];
    }

    public static function definitions(): array
    {
        $definitions = parent::definitions();

        $definitions['bigint UNSIGNED'][1] = new IntegerColumn(ColumnType::BIGINT, dbType: 'bigint', unsigned: true);
        $definitions['integer[]'][1] = new ArrayColumn(dbType: 'integer', column: new IntegerColumn(dbType: 'integer'));
        $definitions['string(126)[][]'][1] = new ArrayColumn(size: 126, dimension: 2, column: new StringColumn(size: 126));

        return $definitions;
    }

    public static function pseudoTypes(): array
    {
        $result = parent::pseudoTypes();
        $result['pk'][1] = new IntegerColumn(primaryKey: true, autoIncrement: true);
        $result['upk'][1] = new IntegerColumn(primaryKey: true, autoIncrement: true, unsigned: true);
        $result['bigpk'][1] = new IntegerColumn(ColumnType::BIGINT, primaryKey: true, autoIncrement: true);
        $result['ubigpk'][1] = new IntegerColumn(ColumnType::BIGINT, primaryKey: true, autoIncrement: true, unsigned: true);

        return $result;
    }

    public static function defaultValueRaw(): array
    {
        $defaultValueRaw = parent::defaultValueRaw();

        $defaultValueRaw[] = [ColumnType::TEXT, 'NULL::"text"', null];
        $defaultValueRaw[] = [ColumnType::TEXT, '(NULL)::"text"', null];
        $defaultValueRaw[] = [ColumnType::TEXT, "'str''ing'::\"text\"", "str'ing"];
        $defaultValueRaw[] = [ColumnType::TEXT, "'str::ing'::\"text\"", 'str::ing'];
        $defaultValueRaw[] = [ColumnType::INTEGER, '(-1)::"int"', -1];
        $defaultValueRaw[] = [ColumnType::BIT, "B'1011'::\"bit\"", 0b1011];
        $defaultValueRaw[] = [ColumnType::STRING, "'\\x737472696e67'", '\\x737472696e67'];
        $defaultValueRaw[] = [ColumnType::BINARY, "'\\x737472696e67'::bytea", 'string'];
        $defaultValueRaw[] = [ColumnType::BINARY, '(1 + 2)::int', new Expression('(1 + 2)::int')];

        return $defaultValueRaw;
    }

    public static function types(): array
    {
        $types = parent::types();

        return [
            ...$types,
            // type, expected type, expected instance of
            'binary' => [ColumnType::BINARY, ColumnType::BINARY, BinaryColumn::class],
            'boolean' => [ColumnType::BOOLEAN, ColumnType::BOOLEAN, BooleanColumn::class],
            'tinyint' => [ColumnType::TINYINT, ColumnType::TINYINT, IntegerColumn::class],
            'smallint' => [ColumnType::SMALLINT, ColumnType::SMALLINT, IntegerColumn::class],
            'integer' => [ColumnType::INTEGER, ColumnType::INTEGER, IntegerColumn::class],
            'bigint' => [ColumnType::BIGINT, ColumnType::BIGINT, IntegerColumn::class],
            'array' => [ColumnType::ARRAY, ColumnType::ARRAY, ArrayColumn::class],
            'structured' => [ColumnType::STRUCTURED, ColumnType::STRUCTURED, StructuredColumn::class],
        ];
    }
}
