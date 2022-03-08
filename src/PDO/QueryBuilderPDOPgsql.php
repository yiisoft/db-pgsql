<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\PDO;

use Generator;
use PDO;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Pdo\PdoValue;
use Yiisoft\Db\Pgsql\Builder\ArrayExpressionBuilder;
use Yiisoft\Db\Pgsql\Builder\JsonExpressionBuilder;
use Yiisoft\Db\Pgsql\DDLQueryBuilder;
use Yiisoft\Db\Pgsql\DMLQueryBuilder;
use Yiisoft\Db\Query\Conditions\LikeCondition;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryBuilder;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Schema\SchemaInterface;

use function array_merge;
use function is_string;

/**
 * The class QueryBuilder is the query builder for PostgresSQL databases.
 */
final class QueryBuilderPDOPgsql extends QueryBuilder
{
    /**
     * Defines a UNIQUE index for {@see createIndex()}.
     */
    public const INDEX_UNIQUE = 'unique';

    /**
     * Defines a B-tree index for {@see createIndex()}.
     */
    public const INDEX_B_TREE = 'btree';

    /**
     * Defines a hash index for {@see createIndex()}.
     */
    public const INDEX_HASH = 'hash';

    /**
     * Defines a GiST index for {@see createIndex()}.
     */
    public const INDEX_GIST = 'gist';

    /**
     * Defines a GIN index for {@see createIndex()}.
     */
    public const INDEX_GIN = 'gin';

    /**
     * @var array mapping from abstract column types (keys) to physical column types (values).
     *
     * @psalm-var array<string, string>
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

    public function __construct(
        private CommandInterface $command,
        private QuoterInterface $quoter,
        private SchemaInterface $schema
    ) {
        $this->ddlBuilder = new DDLQueryBuilder($this);
        $this->dmlBuilder = new DMLQueryBuilder($this);
        parent::__construct($quoter, $schema, $this->ddlBuilder, $this->dmlBuilder);
    }

    public function alterColumn(string $table, string $column, $type): string
    {
        return $this->ddlBuilder->alterColumn($table, $column, $type);
    }

    public function batchInsert(string $table, array $columns, iterable|Generator $rows, array &$params = []): string
    {
        return $this->dmlBuilder->batchInsert($table, $columns, $rows, $params);
    }

    public function checkIntegrity(string $schema = '', string $table = '', bool $check = true): string
    {
        return $this->ddlBuilder->checkIntegrity($schema, $table, $check);
    }

    public function createIndex(string $name, string $table, array|string $columns, $unique = false): string
    {
        return $this->ddlBuilder->createIndex($name, $table, $columns, $unique);
    }

    public function dropIndex(string $name, string $table): string
    {
        return $this->ddlBuilder->dropIndex($name, $table);
    }

    public function renameTable(string $oldName, string $newName): string
    {
        return $this->ddlBuilder->renameTable($oldName, $newName);
    }

    public function command(): CommandInterface
    {
        return $this->command;
    }

    /**
     * Normalizes data to be saved into the table, performing extra preparations and type converting, if necessary.
     *
     * @param string $table the table that data will be saved into.
     * @param array|Query $columns the column data (name => value) to be saved into the table or instance of
     * {@see Query} to perform INSERT INTO ... SELECT SQL statement. Passing of
     * {@see Query}.
     *
     * @return array|Query normalized columns.
     */
    public function normalizeTableRowData(string $table, Query|array $columns): Query|array
    {
        if ($columns instanceof Query) {
            return $columns;
        }

        if (($tableSchema = $this->schema->getTableSchema($table)) !== null) {
            $columnSchemas = $tableSchema->getColumns();
            /** @var mixed $value */
            foreach ($columns as $name => $value) {
                if (
                    isset($columnSchemas[$name]) &&
                    $columnSchemas[$name]->getType() === Schema::TYPE_BINARY &&
                    is_string($value)
                ) {
                    /** explicitly setup PDO param type for binary column */
                    $columns[$name] = new PdoValue($value, PDO::PARAM_LOB);
                }
            }
        }

        return $columns;
    }

    public function quoter(): QuoterInterface
    {
        return $this->quoter;
    }

    public function schema(): SchemaInterface
    {
        return $this->schema;
    }

    public function truncateTable(string $table): string
    {
        return $this->ddlBuilder->truncateTable($table);
    }

    /**
     * Contains array of default condition classes. Extend this method, if you want to change default condition classes
     * for the query builder.
     *
     * @return array
     *
     * See {@see conditionClasses} docs for details.
     */
    protected function defaultConditionClasses(): array
    {
        return array_merge(parent::defaultConditionClasses(), [
            'ILIKE' => LikeCondition::class,
            'NOT ILIKE' => LikeCondition::class,
            'OR ILIKE' => LikeCondition::class,
            'OR NOT ILIKE' => LikeCondition::class,
        ]);
    }

    /**
     * Contains array of default expression builders. Extend this method and override it, if you want to change default
     * expression builders for this query builder.
     *
     * @return array
     *
     * See {@see ExpressionBuilder} docs for details.
     */
    protected function defaultExpressionBuilders(): array
    {
        return array_merge(parent::defaultExpressionBuilders(), [
            ArrayExpression::class => ArrayExpressionBuilder::class,
            JsonExpression::class => JsonExpressionBuilder::class,
        ]);
    }
}
