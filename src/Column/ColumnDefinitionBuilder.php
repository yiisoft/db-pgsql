<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Pgsql\Constant\PgsqlColumnType;
use Yiisoft\Db\QueryBuilder\AbstractColumnDefinitionBuilder;
use Yiisoft\Db\Schema\Column\AbstractArrayColumn;
use Yiisoft\Db\Schema\Column\CollatableColumnInterface;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function str_repeat;
use function version_compare;

final class ColumnDefinitionBuilder extends AbstractColumnDefinitionBuilder
{
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
        'timestamp',
        'timestamptz',
        'time',
        'timetz',
        'interval',
    ];

    protected const TYPES_WITH_SCALE = [
        'decimal',
        'numeric',
    ];

    public function build(ColumnInterface $column): string
    {
        if ($column->isUnsigned()) {
            throw new NotSupportedException('The "unsigned" attribute is not supported by PostgreSQL.');
        }

        return $this->buildType($column)
            . $this->buildNotNull($column)
            . $this->buildPrimaryKey($column)
            . $this->buildUnique($column)
            . $this->buildDefault($column)
            . $this->buildCheck($column)
            . $this->buildCollate($column)
            . $this->buildReferences($column)
            . $this->buildExtra($column);
    }

    public function buildAlter(ColumnInterface $column): string
    {
        return $this->buildType($column)
            . $this->buildExtra($column);
    }

    public function buildType(ColumnInterface $column): string
    {
        if ($column instanceof AbstractArrayColumn) {
            if (!empty($column->getDbType())) {
                $dbType = parent::buildType($column);

                if ($dbType[-1] === ']') {
                    return $dbType;
                }
            } else {
                $dbType = parent::buildType($column->getColumn() ?? $column);
            }

            return $dbType . str_repeat('[]', $column->getDimension());
        }

        return parent::buildType($column);
    }

    protected function buildCollate(ColumnInterface $column): string
    {
        if (!$column instanceof CollatableColumnInterface || empty($column->getCollation())) {
            return '';
        }

        /** @psalm-suppress PossiblyNullArgument */
        return ' COLLATE ' . $this->queryBuilder->getQuoter()->quoteColumnName($column->getCollation());
    }

    protected function getDbType(ColumnInterface $column): string
    {
        $dbType = $column->getDbType();

        /** @psalm-suppress DocblockTypeContradiction */
        return match ($dbType) {
            default => $dbType,
            null => match ($column->getType()) {
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
                ColumnType::STRING => 'varchar(' . ($column->getSize() ?? 255) . ')',
                ColumnType::TEXT => 'text',
                ColumnType::BINARY => 'bytea',
                ColumnType::UUID => 'uuid',
                ColumnType::TIMESTAMP => 'timestamp',
                ColumnType::DATETIME => 'timestamp',
                ColumnType::DATETIMETZ => 'timestamptz',
                ColumnType::TIME => 'time',
                ColumnType::TIMETZ => 'timetz',
                ColumnType::DATE => 'date',
                ColumnType::STRUCTURED => 'jsonb',
                ColumnType::JSON => 'jsonb',
                ColumnType::ENUM => 'varchar',
                PgsqlColumnType::INT4RANGE => 'int4range',
                PgsqlColumnType::INT8RANGE => 'int8range',
                PgsqlColumnType::NUMRANGE => 'numrange',
                PgsqlColumnType::TSRANGE => 'tsrange',
                PgsqlColumnType::TSTZRANGE => 'tstzrange',
                PgsqlColumnType::DATERANGE => 'daterange',
                PgsqlColumnType::INT4MULTIRANGE => 'int4multirange',
                PgsqlColumnType::INT8MULTIRANGE => 'int8multirange',
                PgsqlColumnType::NUMMULTIRANGE => 'nummultirange',
                PgsqlColumnType::TSMULTIRANGE => 'tsmultirange',
                PgsqlColumnType::TSTZMULTIRANGE => 'tstzmultirange',
                PgsqlColumnType::DATEMULTIRANGE => 'datemultirange',
                default => 'varchar',
            },
            'timestamp without time zone' => 'timestamp',
            'timestamp with time zone' => 'timestamptz',
            'time without time zone' => 'time',
            'time with time zone' => 'timetz',
        };
    }

    protected function getDefaultUuidExpression(): string
    {
        $serverVersion = $this->queryBuilder->getServerInfo()->getVersion();

        if (version_compare($serverVersion, '13', '<')) {
            return "uuid_in(overlay(overlay(md5(now()::text || random()::text) placing '4' from 13) placing"
                . ' to_hex(floor(4 * random() + 8)::int)::text from 17)::cstring)';
        }

        return 'gen_random_uuid()';
    }
}
