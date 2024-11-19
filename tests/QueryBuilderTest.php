<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Throwable;
use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\Condition\ArrayOverlapsCondition;
use Yiisoft\Db\QueryBuilder\Condition\JsonOverlapsCondition;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;
use Yiisoft\Db\Tests\Common\CommonQueryBuilderTest;

use function version_compare;

/**
 * @group pgsql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class QueryBuilderTest extends CommonQueryBuilderTest
{
    use TestTrait;

    public function getBuildColumnDefinitionProvider(): array
    {
        return QueryBuilderProvider::buildColumnDefinition();
    }

    protected PdoConnectionInterface $db;

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testAddDefaultValue(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Pgsql\DDLQueryBuilder::addDefaultValue is not supported by PostgreSQL.'
        );

        $qb->addDefaultValue('T_constraints_1', 'CN_pk', 'C_default', 1);

        $db->close();
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'alterColumn')]
    public function testAlterColumn(string|ColumnSchemaInterface $type, string $expected): void
    {
        parent::testAlterColumn($type, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::addForeignKey
     */
    public function testAddForeignKey(
        string $name,
        string $table,
        array|string $columns,
        string $refTable,
        array|string $refColumns,
        string|null $delete,
        string|null $update,
        string $expected
    ): void {
        parent::testAddForeignKey($name, $table, $columns, $refTable, $refColumns, $delete, $update, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::addPrimaryKey
     */
    public function testAddPrimaryKey(string $name, string $table, array|string $columns, string $expected): void
    {
        parent::testAddPrimaryKey($name, $table, $columns, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::addUnique
     */
    public function testAddUnique(string $name, string $table, array|string $columns, string $expected): void
    {
        parent::testAddUnique($name, $table, $columns, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::batchInsert
     */
    public function testBatchInsert(
        string $table,
        iterable $rows,
        array $columns,
        string $expected,
        array $expectedParams = [],
    ): void {
        parent::testBatchInsert($table, $rows, $columns, $expected, $expectedParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::buildCondition
     */
    public function testBuildCondition(
        array|ExpressionInterface|string $condition,
        string|null $expected,
        array $expectedParams
    ): void {
        parent::testBuildCondition($condition, $expected, $expectedParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::buildLikeCondition
     */
    public function testBuildLikeCondition(
        array|ExpressionInterface $condition,
        string $expected,
        array $expectedParams
    ): void {
        parent::testBuildLikeCondition($condition, $expected, $expectedParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::buildFrom
     */
    public function testBuildWithFrom(mixed $table, string $expectedSql, array $expectedParams = []): void
    {
        parent::testBuildWithFrom($table, $expectedSql, $expectedParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::buildWhereExists
     */
    public function testBuildWithWhereExists(string $cond, string $expectedQuerySql): void
    {
        parent::testBuildWithWhereExists($cond, $expectedQuerySql);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testCheckIntegrity(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            ALTER TABLE "public"."item" ENABLE TRIGGER ALL;
            SQL . ' ',
            $qb->checkIntegrity('public', 'item'),
        );

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testCheckIntegrityExecute(): void
    {
        $db = $this->getConnection(true);

        $db->createCommand()->checkIntegrity('public', 'item', false)->execute();
        $command = $db->createCommand(
            <<<SQL
            INSERT INTO {{item}}([[name]], [[category_id]]) VALUES ('invalid', 99999)
            SQL
        );
        $command->execute();

        $db->createCommand()->checkIntegrity('public', 'item')->execute();

        $this->expectException(IntegrityException::class);
        $this->expectExceptionMessage(
            'SQLSTATE[23503]: Foreign key violation: 7 ERROR:  insert or update on table "item" violates foreign key constraint "item_category_id_fkey"'
        );

        $command->execute();

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testCreateTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            CREATE TABLE "test" (
            \t"id" serial PRIMARY KEY,
            \t"name" varchar(255) NOT NULL,
            \t"email" varchar(255) NOT NULL,
            \t"status" integer NOT NULL,
            \t"created_at" timestamp NOT NULL
            )
            SQL,
            $qb->createTable(
                'test',
                [
                    'id' => 'pk',
                    'name' => 'string(255) NOT NULL',
                    'email' => 'string(255) NOT NULL',
                    'status' => 'integer NOT NULL',
                    'created_at' => 'datetime NOT NULL',
                ],
            ),
        );

        $db->close();
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::delete
     */
    public function testDelete(string $table, array|string $condition, string $expectedSQL, array $expectedParams): void
    {
        parent::testDelete($table, $condition, $expectedSQL, $expectedParams);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testDropCommentFromColumn(): void
    {
        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            COMMENT ON COLUMN "customer"."id" IS NULL
            SQL,
            $qb->dropCommentFromColumn('customer', 'id'),
        );

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testDropDefaultValue(): void
    {
        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Pgsql\DDLQueryBuilder::dropDefaultValue is not supported by PostgreSQL.'
        );

        $qb->dropDefaultValue('T_constraints_1', 'CN_pk');

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testDropIndex(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            DROP INDEX "index"
            SQL,
            $qb->dropIndex('{{table}}', 'index'),
        );

        $this->assertSame(
            <<<SQL
            DROP INDEX "schema"."index"
            SQL,
            $qb->dropIndex('schema.table', 'index'),
        );

        $this->assertSame(
            <<<SQL
            DROP INDEX "schema"."index"
            SQL,
            $qb->dropIndex('{{schema.table}}', 'index'),
        );

        $this->assertEquals(
            <<<SQL
            DROP INDEX "schema"."index"
            SQL,
            $qb->dropIndex('{{schema2.table}}', 'schema.index'),
        );

        $this->assertSame(
            <<<SQL
            DROP INDEX "schema"."index"
            SQL,
            $qb->dropIndex('{{schema.%table}}', 'index'),
        );

        $this->assertSame(
            <<<SQL
            DROP INDEX {{%schema.index}}
            SQL,
            $qb->dropIndex('{{%schema.table}}', 'index'),
        );

        $db->close();
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::insert
     */
    public function testInsert(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        parent::testInsert($table, $columns, $params, $expectedSQL, $expectedParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::insertWithReturningPks
     */
    public function testInsertWithReturningPks(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        parent::testInsertWithReturningPks($table, $columns, $params, $expectedSQL, $expectedParams);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testRenameTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            ALTER TABLE "alpha" RENAME TO "alpha-test"
            SQL,
            $qb->renameTable('alpha', 'alpha-test'),
        );

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testResetSequence(): void
    {
        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            SELECT SETVAL('"item_id_seq"',(SELECT COALESCE(MAX("id"),0) FROM "item")+1,false)
            SQL,
            $qb->resetSequence('item'),
        );

        $this->assertSame(
            <<<SQL
            SELECT SETVAL('"item_id_seq"',4,false)
            SQL,
            $qb->resetSequence('item', 4),
        );

        $this->assertEquals(
            <<<SQL
            SELECT SETVAL('"item_id_seq"',1,false)
            SQL,
            $qb->resetSequence('item', '1'),
        );

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testResetSequencePgsql12(): void
    {
        if (version_compare($this->getConnection()->getServerVersion(), '12.0', '<')) {
            $this->markTestSkipped('PostgreSQL < 12.0 does not support GENERATED AS IDENTITY columns.');
        }

        $this->setFixture('pgsql12.sql');

        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            SELECT SETVAL('"item_12_id_seq"',(SELECT COALESCE(MAX("id"),0) FROM "item_12")+1,false)
            SQL,
            $qb->resetSequence('item_12'),
        );

        $this->assertSame(
            <<<SQL
            SELECT SETVAL('"item_12_id_seq"',4,false)
            SQL,
            $qb->resetSequence('item_12', 4),
        );

        $this->assertSame(
            <<<SQL
            SELECT SETVAL('"item_id_seq"',1,false)
            SQL,
            $qb->resetSequence('item', '1'),
        );

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testTruncateTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $sql = $qb->truncateTable('customer');

        $this->assertSame(
            <<<SQL
            TRUNCATE TABLE "customer" RESTART IDENTITY
            SQL,
            $sql,
        );

        $sql = $qb->truncateTable('T_constraints_1');

        $this->assertSame(
            <<<SQL
            TRUNCATE TABLE "T_constraints_1" RESTART IDENTITY
            SQL,
            $sql,
        );

        $db->close();
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::update
     */
    public function testUpdate(
        string $table,
        array $columns,
        array|string $condition,
        array $params,
        string $expectedSql,
        array $expectedParams,
    ): void {
        parent::testUpdate($table, $columns, $condition, $params, $expectedSql, $expectedParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::upsert
     */
    public function testUpsert(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        string $expectedSQL,
        array $expectedParams
    ): void {
        parent::testUpsert($table, $insertColumns, $updateColumns, $expectedSQL, $expectedParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::upsert
     */
    public function testUpsertExecute(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns
    ): void {
        parent::testUpsertExecute($table, $insertColumns, $updateColumns);
    }

    /** @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::selectScalar */
    public function testSelectScalar(array|bool|float|int|string $columns, string $expected): void
    {
        parent::testSelectScalar($columns, $expected);
    }

    public function testArrayOverlapsConditionBuilder(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $sql = $qb->buildExpression(new ArrayOverlapsCondition('column', [1, 2, 3]), $params);

        $this->assertSame('"column"::text[] && ARRAY[:qp0, :qp1, :qp2]::text[]', $sql);
        $this->assertSame([':qp0' => 1, ':qp1' => 2, ':qp2' => 3], $params);

        // Test column as Expression
        $params = [];
        $sql = $qb->buildExpression(new ArrayOverlapsCondition(new Expression('column'), [1, 2, 3]), $params);

        $this->assertSame('column::text[] && ARRAY[:qp0, :qp1, :qp2]::text[]', $sql);
        $this->assertSame([':qp0' => 1, ':qp1' => 2, ':qp2' => 3], $params);

        $db->close();
    }

    public function testJsonOverlapsConditionBuilder(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $sql = $qb->buildExpression(new JsonOverlapsCondition('column', [1, 2, 3]), $params);

        $this->assertSame(
            'ARRAY(SELECT jsonb_array_elements_text("column"::jsonb)) && ARRAY[:qp0, :qp1, :qp2]::text[]',
            $sql
        );
        $this->assertSame([':qp0' => 1, ':qp1' => 2, ':qp2' => 3], $params);

        $db->close();
    }

    /** @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::overlapsCondition */
    public function testOverlapsCondition(iterable|ExpressionInterface $values, int $expectedCount): void
    {
        $db = $this->getConnection();
        $query = new Query($db);

        $count = $query
            ->from('array_and_json_types')
            ->where(new ArrayOverlapsCondition('intarray_col', $values))
            ->count();

        $this->assertSame($expectedCount, $count);

        $count = $query
            ->from('array_and_json_types')
            ->where(new JsonOverlapsCondition('json_col', $values))
            ->count();

        $this->assertSame($expectedCount, $count);

        $count = $query
            ->from('array_and_json_types')
            ->where(new JsonOverlapsCondition('jsonb_col', $values))
            ->count();

        $this->assertSame($expectedCount, $count);

        $db->close();
    }

    /** @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::overlapsCondition */
    public function testOverlapsConditionOperator(iterable|ExpressionInterface $values, int $expectedCount): void
    {
        $db = $this->getConnection();
        $query = new Query($db);

        $count = $query
            ->from('array_and_json_types')
            ->where(['array overlaps', 'intarray_col', $values])
            ->count();

        $this->assertSame($expectedCount, $count);

        $count = $query
            ->from('array_and_json_types')
            ->where(['json overlaps', 'json_col', $values])
            ->count();

        $this->assertSame($expectedCount, $count);

        $count = $query
            ->from('array_and_json_types')
            ->where(['json overlaps', 'jsonb_col', $values])
            ->count();

        $this->assertSame($expectedCount, $count);

        $db->close();
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'buildColumnDefinition')]
    public function testBuildColumnDefinition(string $expected, ColumnSchemaInterface|string $column): void
    {
        parent::testBuildColumnDefinition($expected, $column);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'prepareParam')]
    public function testPrepareParam(string $expected, mixed $value, int $type): void
    {
        parent::testPrepareParam($expected, $value, $type);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'prepareValue')]
    public function testPrepareValue(string $expected, mixed $value): void
    {
        parent::testPrepareValue($expected, $value);
    }
}
