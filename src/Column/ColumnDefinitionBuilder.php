<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\QueryBuilder\AbstractColumnDefinitionBuilder;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;

final class ColumnDefinitionBuilder extends AbstractColumnDefinitionBuilder
{
    protected const CLAUSES = [
        'type',
        'not_null',
        'primary_key',
        'unique',
        'default',
        'check',
        'references',
        'extra',
    ];

    protected const GENERATE_UUID_EXPRESSION = 'gen_random_uuid()';

    protected const TYPES_WITH_SIZE = [
        'bit',
        'bit varying',
        'varbit',
        'decimal',
        'numeric',
        'char',
        'character',
        'bpchar',
        'character varying',
        'varchar',
        'time',
        'timetz',
        'timestamp',
        'timestamptz',
        'interval',
    ];

    protected const TYPES_WITH_SCALE = [
        'decimal',
        'numeric',
    ];

    protected function buildType(ColumnSchemaInterface $column): string
    {
        if ($column instanceof \Yiisoft\Db\Schema\Column\ArrayColumnSchema) {
            return $this->buildType($column->getColumn()) . str_repeat('[]', $column->getDimension());
        }

        return parent::buildType($column);
    }

    protected function getDbType(ColumnSchemaInterface $column): string
    {
        /** @psalm-suppress DocblockTypeContradiction */
        return match ($column->getType()) {
            ColumnType::BOOLEAN => 'boolean',
            ColumnType::BIT => 'varbit',
            ColumnType::TINYINT => $column->isAutoIncrement() ? 'smallserial' : 'smallint',
            ColumnType::SMALLINT => $column->isAutoIncrement() ? 'smallserial' : 'smallint',
            ColumnType::INTEGER => $column->isAutoIncrement() ? 'serial' : 'integer',
            ColumnType::BIGINT => $column->isAutoIncrement() ? 'bigserial' : 'bigint',
            ColumnType::FLOAT => 'real',
            ColumnType::DOUBLE => 'double precision',
            ColumnType::DECIMAL => 'numeric',
            ColumnType::MONEY => 'money',
            ColumnType::CHAR => 'char',
            ColumnType::STRING => 'varchar',
            ColumnType::TEXT => 'text',
            ColumnType::BINARY => 'bytea',
            ColumnType::UUID => 'uuid',
            ColumnType::DATETIME => 'timestamp',
            ColumnType::TIMESTAMP => 'timestamp',
            ColumnType::DATE => 'date',
            ColumnType::TIME => 'time',
            ColumnType::STRUCTURED => 'jsonb',
            ColumnType::JSON => 'jsonb',
            default => 'varchar',
        };
    }
}
