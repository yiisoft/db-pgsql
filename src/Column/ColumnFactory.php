<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\Column\AbstractColumnFactory;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function preg_replace;
use function str_starts_with;
use function substr;

use const PHP_INT_SIZE;

/**
 * @psalm-type ColumnInfo = array{
 *     auto_increment?: bool|string,
 *     check?: string|null,
 *     collation?: string|null,
 *     column?: ColumnInterface,
 *     columns?: array<string, ColumnInterface>,
 *     comment?: string|null,
 *     computed?: bool|string,
 *     dbTimezone?: string,
 *     db_type?: string|null,
 *     default_value?: mixed,
 *     dimension?: int|string,
 *     enum_values?: array|null,
 *     extra?: string|null,
 *     fromResult?: bool,
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
final class ColumnFactory extends AbstractColumnFactory
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
        'abstime' => ColumnType::DATETIME,
        'timestamp' => ColumnType::DATETIME,
        'timestamp without time zone' => ColumnType::DATETIME,
        'timestamp with time zone' => ColumnType::DATETIMETZ,
        'timestamptz' => ColumnType::DATETIMETZ,
        'time' => ColumnType::TIME,
        'time without time zone' => ColumnType::TIME,
        'time with time zone' => ColumnType::TIMETZ,
        'timetz' => ColumnType::TIMETZ,
        'date' => ColumnType::DATE,
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

    public function fromType(string $type, array $info = []): ColumnInterface
    {
        $column = parent::fromType($type, $info);

        if ($column instanceof StructuredColumn) {
            $this->initializeStructuredDefaultValue($column);
        }

        return $column;
    }

    protected function columnDefinitionParser(): ColumnDefinitionParser
    {
        return new ColumnDefinitionParser();
    }

    protected function getColumnClass(string $type, array $info = []): string
    {
        return match ($type) {
            ColumnType::BOOLEAN => BooleanColumn::class,
            ColumnType::BIT => BitColumn::class,
            ColumnType::TINYINT => IntegerColumn::class,
            ColumnType::SMALLINT => IntegerColumn::class,
            ColumnType::INTEGER => IntegerColumn::class,
            ColumnType::BIGINT => PHP_INT_SIZE !== 8
                ? BigIntColumn::class
                : IntegerColumn::class,
            ColumnType::BINARY => BinaryColumn::class,
            ColumnType::ARRAY => ArrayColumn::class,
            ColumnType::STRUCTURED => StructuredColumn::class,
            default => parent::getColumnClass($type, $info),
        };
    }

    protected function normalizeNotNullDefaultValue(string $defaultValue, ColumnInterface $column): mixed
    {
        /** @var string $value */
        $value = preg_replace("/::[^:']+$/", '$1', $defaultValue);

        if (str_starts_with($value, "B'") && $value[-1] === "'") {
            return $column->phpTypecast(substr($value, 2, -1));
        }

        $value = parent::normalizeNotNullDefaultValue($value, $column);

        if ($value instanceof Expression) {
            return new Expression($defaultValue);
        }

        return $value;
    }

    /**
     * Initializes the default value for structured columns.
     */
    private function initializeStructuredDefaultValue(StructuredColumn $column): void
    {
        /** @psalm-var array|null $defaultValue */
        $defaultValue = $column->getDefaultValue();

        if (is_array($defaultValue)) {
            foreach ($column->getColumns() as $structuredColumnName => $structuredColumn) {
                if (isset($defaultValue[$structuredColumnName])) {
                    $structuredColumn->defaultValue($defaultValue[$structuredColumnName]);

                    if ($structuredColumn instanceof StructuredColumn) {
                        $this->initializeStructuredDefaultValue($structuredColumn);
                    }
                }
            }
        }
    }
}
