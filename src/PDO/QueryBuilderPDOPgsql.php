<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\PDO;

use Yiisoft\Db\Pgsql\DDLQueryBuilder;
use Yiisoft\Db\Pgsql\DMLQueryBuilder;
use Yiisoft\Db\Pgsql\DQLQueryBuilder;
use Yiisoft\Db\QueryBuilder\QueryBuilder;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Schema\SchemaInterface;

/**
 * The class QueryBuilder is the query builder for PostgresSQL databases.
 */
final class QueryBuilderPDOPgsql extends QueryBuilder
{
    /**
     * Defines a B-tree index method for {@see createIndex()}.
     */
    public const INDEX_B_TREE = 'btree';

    /**
     * Defines a hash index method for {@see createIndex()}.
     */
    public const INDEX_HASH = 'hash';

    /**
     * Defines a GiST index method for {@see createIndex()}.
     */
    public const INDEX_GIST = 'gist';

    /**
     * Defines a GIN index method for {@see createIndex()}.
     */
    public const INDEX_GIN = 'gin';

    /**
     * Defines a BRIN index method for {@see createIndex()}.
     */
    public const INDEX_BRIN = 'brin';

    /**
     * @var array mapping from abstract column types (keys) to physical column types (values).
     *
     * @psalm-var string[]
     */
    protected array $typeMap = [
        Schema::TYPE_PK => 'serial NOT NULL PRIMARY KEY',
        Schema::TYPE_UPK => 'serial NOT NULL PRIMARY KEY',
        Schema::TYPE_BIGPK => 'bigserial NOT NULL PRIMARY KEY',
        Schema::TYPE_UBIGPK => 'bigserial NOT NULL PRIMARY KEY',
        Schema::TYPE_CHAR => 'char(1)',
        Schema::TYPE_STRING => 'varchar(255)',
        Schema::TYPE_TEXT => 'text',
        Schema::TYPE_TINYINT => 'smallint',
        Schema::TYPE_SMALLINT => 'smallint',
        Schema::TYPE_INTEGER => 'integer',
        Schema::TYPE_BIGINT => 'bigint',
        Schema::TYPE_FLOAT => 'double precision',
        Schema::TYPE_DOUBLE => 'double precision',
        Schema::TYPE_DECIMAL => 'numeric(10,0)',
        Schema::TYPE_DATETIME => 'timestamp(0)',
        Schema::TYPE_TIMESTAMP => 'timestamp(0)',
        Schema::TYPE_TIME => 'time(0)',
        Schema::TYPE_DATE => 'date',
        Schema::TYPE_BINARY => 'bytea',
        Schema::TYPE_BOOLEAN => 'boolean',
        Schema::TYPE_MONEY => 'numeric(19,4)',
        Schema::TYPE_JSON => 'jsonb',
    ];
    private DDLQueryBuilder $ddlBuilder;
    private DMLQueryBuilder $dmlBuilder;
    private DQLQueryBuilder $dqlBuilder;

    public function __construct(QuoterInterface $quoter, SchemaInterface $schema)
    {
        $this->ddlBuilder = new DDLQueryBuilder($this, $quoter, $schema);
        $this->dmlBuilder = new DMLQueryBuilder($this, $quoter, $schema);
        $this->dqlBuilder = new DQLQueryBuilder($this, $quoter, $schema);
        parent::__construct($quoter, $schema, $this->ddlBuilder, $this->dmlBuilder, $this->dqlBuilder);
    }
}
