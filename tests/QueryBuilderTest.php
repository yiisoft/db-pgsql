<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Throwable;
use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\SchemaBuilderTrait;
use Yiisoft\Db\Tests\Common\CommonQueryBuilderTest;

use function version_compare;

/**
 * @group pgsql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class QueryBuilderTest extends CommonQueryBuilderTest
{
    use SchemaBuilderTrait;
    use TestTrait;

    protected ConnectionPDOInterface $db;

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

        $qb->addDefaultValue('CN_pk', 'T_constraints_1', 'C_default', 1);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testAlterColumn(): void
    {
        $this->db = $this->getConnection();

        $qb = $this->db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255)
            SQL,
            $qb->alterColumn('foo1', 'bar', 'varchar(255)'),
        );

        $this->assertSame(
            <<<SQL
            ALTER TABLE "foo1" ALTER COLUMN "bar" SET NOT null
            SQL,
            $qb->alterColumn('foo1', 'bar', 'SET NOT null'),
        );

        $this->assertSame(
            <<<SQL
            ALTER TABLE "foo1" ALTER COLUMN "bar" drop default
            SQL,
            $qb->alterColumn('foo1', 'bar', 'drop default'),
        );

        $this->assertSame(
            <<<SQL
            ALTER TABLE "foo1" ALTER COLUMN "bar" reset xyz
            SQL,
            $qb->alterColumn('foo1', 'bar', 'reset xyz'),
        );

        $this->assertSame(
            <<<SQL
            ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255)
            SQL,
            $qb->alterColumn('foo1', 'bar', $this->string(255)),
        );

        $this->assertSame(
            <<<SQL
            ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255) USING bar::varchar
            SQL,
            $qb->alterColumn('foo1', 'bar', 'varchar(255) USING bar::varchar'),
        );

        $this->assertSame(
            <<<SQL
            ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255) using cast("bar" as varchar)
            SQL,
            $qb->alterColumn('foo1', 'bar', 'varchar(255) using cast("bar" as varchar)'),
        );

        $this->assertSame(
            <<<SQL
            ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" SET NOT NULL
            SQL,
            $qb->alterColumn('foo1', 'bar', $this->string(255)->notNull()),
        );

        $this->assertSame(
            <<<SQL
            ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" SET DEFAULT NULL, ALTER COLUMN "bar" DROP NOT NULL
            SQL,
            $qb->alterColumn('foo1', 'bar', $this->string(255)->null()),
        );

        $this->assertSame(
            <<<SQL
            ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" SET DEFAULT 'xxx', ALTER COLUMN "bar" DROP NOT NULL
            SQL,
            $qb->alterColumn('foo1', 'bar', $this->string(255)->null()->defaultValue('xxx')),
        );

        $this->assertSame(
            <<<SQL
            ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ADD CONSTRAINT foo1_bar_check CHECK (char_length(bar) > 5)
            SQL,
            $qb->alterColumn('foo1', 'bar', $this->string(255)->check('char_length(bar) > 5')),
        );

        $this->assertSame(
            <<<SQL
            ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" SET DEFAULT ''
            SQL,
            $qb->alterColumn('foo1', 'bar', $this->string(255)->defaultValue('')),
        );

        $this->assertSame(
            <<<SQL
            ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" SET DEFAULT 'AbCdE'
            SQL,
            $qb->alterColumn('foo1', 'bar', $this->string(255)->defaultValue('AbCdE')),
        );

        $this->assertSame(
            <<<SQL
            ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE timestamp(0), ALTER COLUMN "bar" SET DEFAULT CURRENT_TIMESTAMP
            SQL,
            $qb->alterColumn('foo1', 'bar', $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')),
        );

        $this->assertSame(
            <<<SQL
            ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(30), ADD UNIQUE ("bar")
            SQL,
            $qb->alterColumn('foo1', 'bar', $this->string(30)->unique()),
        );
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::addForeignKey()
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
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::addPrimaryKey()
     */
    public function testAddPrimaryKey(string $name, string $table, array|string $columns, string $expected): void
    {
        parent::testAddPrimaryKey($name, $table, $columns, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::addUnique()
     */
    public function testAddUnique(string $name, string $table, array|string $columns, string $expected): void
    {
        parent::testAddUnique($name, $table, $columns, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::batchInsert()
     */
    public function testBatchInsert(string $table, array $columns, array $rows, string $expected): void
    {
        parent::testBatchInsert($table, $columns, $rows, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::buildCondition()
     */
    public function testBuildCondition(
        array|ExpressionInterface|string $condition,
        string|null $expected,
        array $expectedParams
    ): void {
        parent::testBuildCondition($condition, $expected, $expectedParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::buildLikeCondition()
     */
    public function testBuildLikeCondition(
        array|ExpressionInterface $condition,
        string $expected,
        array $expectedParams
    ): void {
        parent::testBuildLikeCondition($condition, $expected, $expectedParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::buildFrom()
     */
    public function testBuildWithFrom(mixed $table, string $expectedSql, array $expectedParams = []): void
    {
        parent::testBuildWithFrom($table, $expectedSql, $expectedParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::buildWhereExists()
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
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testCheckIntegrityExecute(): void
    {
        $db = $this->getConnection();

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
            \t"id" serial NOT NULL PRIMARY KEY,
            \t"name" varchar(255) NOT NULL,
            \t"email" varchar(255) NOT NULL,
            \t"status" integer NOT NULL,
            \t"created_at" timestamp(0) NOT NULL
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
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::delete()
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

        $qb->dropDefaultValue('CN_pk', 'T_constraints_1');
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
            $qb->dropIndex('index', '{{table}}'),
        );

        $this->assertSame(
            <<<SQL
            DROP INDEX "schema"."index"
            SQL,
            $qb->dropIndex('index', 'schema.table'),
        );

        $this->assertSame(
            <<<SQL
            DROP INDEX "schema"."index"
            SQL,
            $qb->dropIndex('index', '{{schema.table}}'),
        );

        $this->assertEquals(
            <<<SQL
            DROP INDEX "schema"."index"
            SQL,
            $qb->dropIndex('schema.index', '{{schema2.table}}'),
        );

        $this->assertSame(
            <<<SQL
            DROP INDEX "schema"."index"
            SQL,
            $qb->dropIndex('index', '{{schema.%table}}'),
        );

        $this->assertSame(
            <<<SQL
            DROP INDEX {{%schema.index}}
            SQL,
            $qb->dropIndex('index', '{{%schema.table}}'),
        );
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::insert()
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
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::insertEx()
     */
    public function testInsertEx(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        parent::testInsertEx($table, $columns, $params, $expectedSQL, $expectedParams);
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
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testResetSequenceNoAssociatedException(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("There is not sequence associated with table 'constraints'.");

        $qb->resetSequence('constraints');
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
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testResetSequenceTableNoExistException(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table not found: noExist');

        $qb->resetSequence('noExist', 1);
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
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::update()
     */
    public function testUpdate(
        string $table,
        array $columns,
        array|string $condition,
        string $expectedSQL,
        array $expectedParams
    ): void {
        parent::testUpdate($table, $columns, $condition, $expectedSQL, $expectedParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::upsert()
     */
    public function testUpsert(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        string|array $expectedSQL,
        array $expectedParams
    ): void {
        parent::testUpsert($table, $insertColumns, $updateColumns, $expectedSQL, $expectedParams);
    }
}
