<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Schema\Column\AbstractColumnFactory;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;

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
        'tsquery' => ColumnType::STRING,
        'tsvector' => ColumnType::STRING,
        'txid_snapshot' => ColumnType::STRING,
        'unknown' => ColumnType::STRING,
        'uuid' => ColumnType::STRING,
        'xml' => ColumnType::STRING,
        'json' => ColumnType::JSON,
        'jsonb' => ColumnType::JSON,
    ];

    /**
     * @psalm-param ColumnType::* $type
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
            /** @psalm-suppress ArgumentTypeCoercion */
            $column = match ($type) {
                ColumnType::BOOLEAN => new BooleanColumnSchema($type),
                ColumnType::BIT => new BitColumnSchema($type),
                ColumnType::TINYINT => new IntegerColumnSchema($type),
                ColumnType::SMALLINT => new IntegerColumnSchema($type),
                ColumnType::INTEGER => new IntegerColumnSchema($type),
                ColumnType::BIGINT => PHP_INT_SIZE !== 8
                    ? new BigIntColumnSchema($type)
                    : new IntegerColumnSchema($type),
                ColumnType::BINARY => new BinaryColumnSchema($type),
                ColumnType::STRUCTURED => (new StructuredColumnSchema($type))->columns($info['columns'] ?? []),
                default => parent::fromType($type, $info),
            };
        }

        return $column->load($info);
    }

    protected function getType(string $dbType, array $info = []): string
    {
        return self::TYPE_MAP[$dbType] ?? ColumnType::STRING;
    }

    protected function isDbType(string $dbType): bool
    {
        return isset(self::TYPE_MAP[$dbType]);
    }
}
