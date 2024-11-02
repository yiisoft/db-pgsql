<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Schema\Column\AbstractColumnFactory;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;

use const PHP_INT_SIZE;

/**
 * @psalm-type ColumnInfo = array{
 *     auto_increment?: bool|string,
 *     check?: string|null,
 *     column?: ColumnSchemaInterface,
 *     columns?: array<string, ColumnSchemaInterface>,
 *     comment?: string|null,
 *     computed?: bool|string,
 *     db_type?: string|null,
 *     default_value?: mixed,
 *     dimension?: int|string,
 *     enum_values?: array|null,
 *     extra?: string|null,
 *     primary_key?: bool|string,
 *     name?: string,
 *     not_null?: bool|string|null,
 *     reference?: ForeignKeyConstraint|null,
 *     sequence_name?: string|null,
 *     scale?: int|string|null,
 *     schema?: string|null,
 *     size?: int|string|null,
 *     table?: string|null,
 *     type?: string,
 *     unique?: bool|string,
 * }
 */
class ColumnFactory extends AbstractColumnFactory
{
    /**
     * The mapping from physical column types (keys) to abstract column types (values).
     *
     * @link https://www.postgresql.org/docs/current/datatype.html#DATATYPE-TABLE
     *
     * @var string[]
     * @psalm-var array<string, ColumnType::*>
     */
    protected const TYPE_MAP = [
        'bool' => ColumnType::BOOLEAN,
        'boolean' => ColumnType::BOOLEAN,
        'bit' => ColumnType::BIT,
        'bit varying' => ColumnType::BIT,
        'varbit' => ColumnType::BIT,
        'smallint' => ColumnType::SMALLINT,
        'int2' => ColumnType::SMALLINT,
        'smallserial' => ColumnType::SMALLINT,
        'serial2' => ColumnType::SMALLINT,
        'int4' => ColumnType::INTEGER,
        'int' => ColumnType::INTEGER,
        'integer' => ColumnType::INTEGER,
        'serial' => ColumnType::INTEGER,
        'serial4' => ColumnType::INTEGER,
        'bigint' => ColumnType::BIGINT,
        'int8' => ColumnType::BIGINT,
        'bigserial' => ColumnType::BIGINT,
        'serial8' => ColumnType::BIGINT,
        'oid' => ColumnType::BIGINT, // shouldn't be used. it's pg internal!
        'pg_lsn' => ColumnType::BIGINT,
        'real' => ColumnType::FLOAT,
        'float4' => ColumnType::FLOAT,
        'float8' => ColumnType::DOUBLE,
        'double precision' => ColumnType::DOUBLE,
        'decimal' => ColumnType::DECIMAL,
        'numeric' => ColumnType::DECIMAL,
        'money' => ColumnType::MONEY,
        'char' => ColumnType::CHAR,
        'character' => ColumnType::CHAR,
        'bpchar' => ColumnType::CHAR,
        'character varying' => ColumnType::STRING,
        'varchar' => ColumnType::STRING,
        'text' => ColumnType::TEXT,
        'bytea' => ColumnType::BINARY,
        'date' => ColumnType::DATE,
        'time' => ColumnType::TIME,
        'time without time zone' => ColumnType::TIME,
        'time with time zone' => ColumnType::TIME,
        'timetz' => ColumnType::TIME,
        'timestamp' => ColumnType::TIMESTAMP,
        'timestamp without time zone' => ColumnType::TIMESTAMP,
        'timestamp with time zone' => ColumnType::TIMESTAMP,
        'timestamptz' => ColumnType::TIMESTAMP,
        'abstime' => ColumnType::TIMESTAMP,
        'interval' => ColumnType::STRING,
        'box' => ColumnType::STRING,
        'circle' => ColumnType::STRING,
        'point' => ColumnType::STRING,
        'line' => ColumnType::STRING,
        'lseg' => ColumnType::STRING,
        'polygon' => ColumnType::STRING,
        'path' => ColumnType::STRING,
        'cidr' => ColumnType::STRING,
        'inet' => ColumnType::STRING,
        'macaddr' => ColumnType::STRING,
        'macaddr8' => ColumnType::STRING,
        'tsquery' => ColumnType::STRING,
        'tsvector' => ColumnType::STRING,
        'txid_snapshot' => ColumnType::STRING,
        'unknown' => ColumnType::STRING,
        'uuid' => ColumnType::STRING,
        'xml' => ColumnType::STRING,
        'json' => ColumnType::JSON,
        'jsonb' => ColumnType::JSON,
    ];

    protected function getColumnClass(string $type, array $info = []): string
    {
        return match ($type) {
            ColumnType::BOOLEAN => BooleanColumnSchema::class,
            ColumnType::BIT => BitColumnSchema::class,
            ColumnType::TINYINT => IntegerColumnSchema::class,
            ColumnType::SMALLINT => IntegerColumnSchema::class,
            ColumnType::INTEGER => IntegerColumnSchema::class,
            ColumnType::BIGINT => PHP_INT_SIZE !== 8
                ? BigIntColumnSchema::class
                : IntegerColumnSchema::class,
            ColumnType::BINARY => BinaryColumnSchema::class,
            ColumnType::ARRAY => ArrayColumnSchema::class,
            ColumnType::STRUCTURED => StructuredColumnSchema::class,
            default => parent::getColumnClass($type, $info),
        };
    }
}
