<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Pgsql\Schema;
use Yiisoft\Db\Schema\Column\AbstractColumnFactory;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use const PHP_INT_SIZE;

/**
 * @psalm-type ColumnInfo = array{
 *     allow_null?: bool|string|null,
 *     auto_increment?: bool|string,
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
 *     precision?: int|string|null,
 *     sequence_name?: string|null,
 *     scale?: int|string|null,
 *     schema?: string|null,
 *     size?: int|string|null,
 *     table?: string|null,
 *     type?: string,
 * }
 */
final class ColumnFactory extends AbstractColumnFactory
{
    /**
     * The mapping from physical column types (keys) to abstract column types (values).
     *
     * @link https://www.postgresql.org/docs/current/datatype.html#DATATYPE-TABLE
     *
     * @var string[]
     *
     * @psalm-suppress MissingClassConstType
     */
    private const TYPE_MAP = [
        'bool' => SchemaInterface::TYPE_BOOLEAN,
        'boolean' => SchemaInterface::TYPE_BOOLEAN,
        'bit' => SchemaInterface::TYPE_BIT,
        'bit varying' => SchemaInterface::TYPE_BIT,
        'varbit' => SchemaInterface::TYPE_BIT,
        'smallint' => SchemaInterface::TYPE_SMALLINT,
        'int2' => SchemaInterface::TYPE_SMALLINT,
        'smallserial' => SchemaInterface::TYPE_SMALLINT,
        'serial2' => SchemaInterface::TYPE_SMALLINT,
        'int4' => SchemaInterface::TYPE_INTEGER,
        'int' => SchemaInterface::TYPE_INTEGER,
        'integer' => SchemaInterface::TYPE_INTEGER,
        'serial' => SchemaInterface::TYPE_INTEGER,
        'serial4' => SchemaInterface::TYPE_INTEGER,
        'bigint' => SchemaInterface::TYPE_BIGINT,
        'int8' => SchemaInterface::TYPE_BIGINT,
        'bigserial' => SchemaInterface::TYPE_BIGINT,
        'serial8' => SchemaInterface::TYPE_BIGINT,
        'oid' => SchemaInterface::TYPE_BIGINT, // shouldn't be used. it's pg internal!
        'pg_lsn' => SchemaInterface::TYPE_BIGINT,
        'real' => SchemaInterface::TYPE_FLOAT,
        'float4' => SchemaInterface::TYPE_FLOAT,
        'float8' => SchemaInterface::TYPE_DOUBLE,
        'double precision' => SchemaInterface::TYPE_DOUBLE,
        'decimal' => SchemaInterface::TYPE_DECIMAL,
        'numeric' => SchemaInterface::TYPE_DECIMAL,
        'money' => SchemaInterface::TYPE_MONEY,
        'char' => SchemaInterface::TYPE_CHAR,
        'character' => SchemaInterface::TYPE_CHAR,
        'bpchar' => SchemaInterface::TYPE_CHAR,
        'character varying' => SchemaInterface::TYPE_STRING,
        'varchar' => SchemaInterface::TYPE_STRING,
        'text' => SchemaInterface::TYPE_TEXT,
        'bytea' => SchemaInterface::TYPE_BINARY,
        'date' => SchemaInterface::TYPE_DATE,
        'time' => SchemaInterface::TYPE_TIME,
        'time without time zone' => SchemaInterface::TYPE_TIME,
        'time with time zone' => SchemaInterface::TYPE_TIME,
        'timetz' => SchemaInterface::TYPE_TIME,
        'timestamp' => SchemaInterface::TYPE_TIMESTAMP,
        'timestamp without time zone' => SchemaInterface::TYPE_TIMESTAMP,
        'timestamp with time zone' => SchemaInterface::TYPE_TIMESTAMP,
        'timestamptz' => SchemaInterface::TYPE_TIMESTAMP,
        'abstime' => SchemaInterface::TYPE_TIMESTAMP,
        'interval' => SchemaInterface::TYPE_STRING,
        'box' => SchemaInterface::TYPE_STRING,
        'circle' => SchemaInterface::TYPE_STRING,
        'point' => SchemaInterface::TYPE_STRING,
        'line' => SchemaInterface::TYPE_STRING,
        'lseg' => SchemaInterface::TYPE_STRING,
        'polygon' => SchemaInterface::TYPE_STRING,
        'path' => SchemaInterface::TYPE_STRING,
        'cidr' => SchemaInterface::TYPE_STRING,
        'inet' => SchemaInterface::TYPE_STRING,
        'macaddr' => SchemaInterface::TYPE_STRING,
        'tsquery' => SchemaInterface::TYPE_STRING,
        'tsvector' => SchemaInterface::TYPE_STRING,
        'txid_snapshot' => SchemaInterface::TYPE_STRING,
        'unknown' => SchemaInterface::TYPE_STRING,
        'uuid' => SchemaInterface::TYPE_STRING,
        'xml' => SchemaInterface::TYPE_STRING,
        'json' => SchemaInterface::TYPE_JSON,
        'jsonb' => SchemaInterface::TYPE_JSON,
    ];

    /**
     * @psalm-param ColumnInfo $info
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function fromType(string $type, array $info = []): ColumnSchemaInterface
    {
        $dimension = (int)($info['dimension'] ?? 0);

        if ($dimension > 0) {
            unset($info['dimension']);
            $column = (new ArrayColumnSchema())
                ->dimension($dimension)
                ->column($this->fromType($type, $info));
        } else {
            $column = match ($type) {
                SchemaInterface::TYPE_BOOLEAN => new BooleanColumnSchema($type),
                SchemaInterface::TYPE_BIT => new BitColumnSchema($type),
                SchemaInterface::TYPE_TINYINT => new IntegerColumnSchema($type),
                SchemaInterface::TYPE_SMALLINT => new IntegerColumnSchema($type),
                SchemaInterface::TYPE_INTEGER => new IntegerColumnSchema($type),
                SchemaInterface::TYPE_BIGINT => PHP_INT_SIZE !== 8
                    ? new BigIntColumnSchema($type)
                    : new IntegerColumnSchema($type),
                SchemaInterface::TYPE_BINARY => new BinaryColumnSchema($type),
                Schema::TYPE_STRUCTURED => (new StructuredColumnSchema($type))->columns($info['columns'] ?? []),
                default => parent::fromType($type, $info),
            };
        }

        return $column;
    }

    protected function getType(string $dbType, array $info = []): string
    {
        return self::TYPE_MAP[$dbType] ?? SchemaInterface::TYPE_STRING;
    }
}
