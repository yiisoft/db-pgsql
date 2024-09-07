<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\QueryBuilder\AbstractQueryBuilder;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

/**
 * Implements the PostgreSQL Server specific query builder.
 */
final class QueryBuilder extends AbstractQueryBuilder
{
    /**
     * @var array Mapping from abstract column types (keys) to physical column types (values).
     *
     * @psalm-var string[]
     */
    protected array $typeMap = [
        PseudoType::PK => 'serial NOT NULL PRIMARY KEY',
        PseudoType::UPK => 'serial NOT NULL PRIMARY KEY',
        PseudoType::BIGPK => 'bigserial NOT NULL PRIMARY KEY',
        PseudoType::UBIGPK => 'bigserial NOT NULL PRIMARY KEY',
        ColumnType::CHAR => 'char(1)',
        ColumnType::STRING => 'varchar(255)',
        ColumnType::TEXT => 'text',
        ColumnType::TINYINT => 'smallint',
        ColumnType::SMALLINT => 'smallint',
        ColumnType::INTEGER => 'integer',
        ColumnType::BIGINT => 'bigint',
        ColumnType::FLOAT => 'double precision',
        ColumnType::DOUBLE => 'double precision',
        ColumnType::DECIMAL => 'numeric(10,0)',
        ColumnType::DATETIME => 'timestamp(0)',
        ColumnType::TIMESTAMP => 'timestamp(0)',
        ColumnType::TIME => 'time(0)',
        ColumnType::DATE => 'date',
        ColumnType::BINARY => 'bytea',
        ColumnType::BOOLEAN => 'boolean',
        ColumnType::MONEY => 'numeric(19,4)',
        ColumnType::JSON => 'jsonb',
        ColumnType::UUID => 'uuid',
        PseudoType::UUID_PK => 'uuid PRIMARY KEY',
    ];

    public function __construct(QuoterInterface $quoter, SchemaInterface $schema)
    {
        $ddlBuilder = new DDLQueryBuilder($this, $quoter, $schema);
        $dmlBuilder = new DMLQueryBuilder($this, $quoter, $schema);
        $dqlBuilder = new DQLQueryBuilder($this, $quoter);
        parent::__construct($quoter, $schema, $ddlBuilder, $dmlBuilder, $dqlBuilder);
    }
}
