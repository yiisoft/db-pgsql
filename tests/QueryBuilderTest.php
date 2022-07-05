<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Closure;
use Throwable;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Pgsql\QueryBuilder;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\TestSupport\TestQueryBuilderTrait;

use function version_compare;

/**
 * @group pgsql
 */
final class QueryBuilderTest extends TestCase
{
    use TestQueryBuilderTrait;

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::addDropChecksProvider
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testAddDropCheck(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::addDropForeignKeysProvider
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testAddDropForeignKey(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::addDropPrimaryKeysProvider
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testAddDropPrimaryKey(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::addDropUniquesProvider
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testAddDropUnique(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    public function testAlterColumn(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $expected = 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255)';
        $sql = $qb->alterColumn('foo1', 'bar', 'varchar(255)');
        $this->assertEquals($expected, $sql);

        $expected = 'ALTER TABLE "foo1" ALTER COLUMN "bar" SET NOT null';
        $sql = $qb->alterColumn('foo1', 'bar', 'SET NOT null');
        $this->assertEquals($expected, $sql);

        $expected = 'ALTER TABLE "foo1" ALTER COLUMN "bar" drop default';
        $sql = $qb->alterColumn('foo1', 'bar', 'drop default');
        $this->assertEquals($expected, $sql);

        $expected = 'ALTER TABLE "foo1" ALTER COLUMN "bar" reset xyz';
        $sql = $qb->alterColumn('foo1', 'bar', 'reset xyz');
        $this->assertEquals($expected, $sql);

        $expected = 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255)';

        $sql = $qb->alterColumn('foo1', 'bar', $this->string(255));
        $this->assertEquals($expected, $sql);

        $expected = 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255) USING bar::varchar';

        $sql = $qb->alterColumn('foo1', 'bar', 'varchar(255) USING bar::varchar');
        $this->assertEquals($expected, $sql);

        $expected = 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255) using cast("bar" as varchar)';

        $sql = $qb->alterColumn('foo1', 'bar', 'varchar(255) using cast("bar" as varchar)');
        $this->assertEquals($expected, $sql);

        $expected = 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" SET NOT NULL';
        $sql = $qb->alterColumn('foo1', 'bar', $this->string(255)->notNull());
        $this->assertEquals($expected, $sql);

        $expected = 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" SET DEFAULT NULL, ALTER COLUMN "bar" DROP NOT NULL';
        $sql = $qb->alterColumn('foo1', 'bar', $this->string(255)->null());
        $this->assertEquals($expected, $sql);

        $expected = 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" SET DEFAULT \'xxx\', ALTER COLUMN "bar" DROP NOT NULL';
        $sql = $qb->alterColumn('foo1', 'bar', $this->string(255)->null()->defaultValue('xxx'));
        $this->assertEquals($expected, $sql);

        $expected = 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ADD CONSTRAINT foo1_bar_check CHECK (char_length(bar) > 5)';
        $sql = $qb->alterColumn('foo1', 'bar', $this->string(255)->check('char_length(bar) > 5'));
        $this->assertEquals($expected, $sql);

        $expected = 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" SET DEFAULT \'\'';
        $sql = $qb->alterColumn('foo1', 'bar', $this->string(255)->defaultValue(''));
        $this->assertEquals($expected, $sql);

        $expected = 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" SET DEFAULT \'AbCdE\'';
        $sql = $qb->alterColumn('foo1', 'bar', $this->string(255)->defaultValue('AbCdE'));
        $this->assertEquals($expected, $sql);

        $expected = 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE timestamp(0), ALTER COLUMN "bar" SET DEFAULT CURRENT_TIMESTAMP';
        $sql = $qb->alterColumn('foo1', 'bar', $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'));
        $this->assertEquals($expected, $sql);

        $expected = 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(30), ADD UNIQUE ("bar")';
        $sql = $qb->alterColumn('foo1', 'bar', $this->string(30)->unique());
        $this->assertEquals($expected, $sql);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::batchInsertProvider
     *
     * @param string $table
     * @param array $columns
     * @param array $value
     * @param string $expected
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testBatchInsert(string $table, array $columns, array $value, string $expected): void
    {
        $db = $this->getConnection();
        $this->assertEquals($expected, $db->getQueryBuilder()->batchInsert($table, $columns, $value));
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::buildConditionsProvider
     *
     * @param array|ExpressionInterface|string $condition
     * @param string $expected
     * @param array $expectedParams
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testBuildCondition(
        array|ExpressionInterface|string $condition,
        string $expected,
        array $expectedParams
    ): void {
        $db = $this->getConnection();
        $query = (new Query($db))->where($condition);
        [$sql, $params] = $db->getQueryBuilder()->build($query);
        $replacedQuotes = $this->replaceQuotes($expected);

        $this->assertIsString($replacedQuotes);
        $this->assertEquals('SELECT *' . (empty($expected) ? '' : ' WHERE ' . $replacedQuotes), $sql);
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::buildFilterConditionProvider
     *
     * @param array $condition
     * @param string $expected
     * @param array $expectedParams
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testBuildFilterCondition(array $condition, string $expected, array $expectedParams): void
    {
        $db = $this->getConnection();
        $query = (new Query($db))->filterWhere($condition);
        [$sql, $params] = $db->getQueryBuilder()->build($query);
        $replacedQuotes = $this->replaceQuotes($expected);

        $this->assertIsString($replacedQuotes);
        $this->assertEquals('SELECT *' . (empty($expected) ? '' : ' WHERE ' . $replacedQuotes), $sql);
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::buildFromDataProvider
     *
     * @param string $table
     * @param string $expected
     *
     * @throws Exception
     */
    public function testBuildFrom(string $table, string $expected): void
    {
        $db = $this->getConnection();
        $params = [];
        $sql = $db->getQueryBuilder()->buildFrom([$table], $params);
        $replacedQuotes = $this->replaceQuotes($expected);

        $this->assertIsString($replacedQuotes);
        $this->assertEquals('FROM ' . $replacedQuotes, $sql);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::buildLikeConditionsProvider
     *
     * @param array|ExpressionInterface $condition
     * @param string $expected
     * @param array $expectedParams
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testBuildLikeCondition(array|ExpressionInterface $condition, string $expected, array $expectedParams): void
    {
        $db = $this->getConnection();
        $query = (new Query($db))->where($condition);
        [$sql, $params] = $db->getQueryBuilder()->build($query);
        $replacedQuotes = $this->replaceQuotes($expected);

        $this->assertIsString($replacedQuotes);
        $this->assertEquals('SELECT *' . (empty($expected) ? '' : ' WHERE ' . $replacedQuotes), $sql);
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::buildExistsParamsProvider
     *
     * @param string $cond
     * @param string $expectedQuerySql
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testBuildWhereExists(string $cond, string $expectedQuerySql): void
    {
        $db = $this->getConnection();
        $expectedQueryParams = [];
        $subQuery = new Query($db);
        $subQuery->select('1')->from('Website w');
        $query = new Query($db);
        $query->select('id')->from('TotalExample t')->where([$cond, $subQuery]);
        [$actualQuerySql, $actualQueryParams] = $db->getQueryBuilder()->build($query);
        $this->assertEquals($expectedQuerySql, $actualQuerySql);
        $this->assertEquals($expectedQueryParams, $actualQueryParams);
    }

    public function testCheckIntegrity(): void
    {
        $this->assertEqualsWithoutLE(
            <<<SQL
            ALTER TABLE "public"."item" ENABLE TRIGGER ALL;
            SQL . ' ',
            $this->getConnection()->getQueryBuilder()->checkIntegrity('public', 'item'),
        );
    }

    /**
     * @throws Exception|InvalidConfigException|Throwable
     */
    public function testCheckIntegrityExecute(): void
    {
        $db = $this->getConnection();
        $db->createCommand()->checkIntegrity('public', 'item', false)->execute();
        $sql = 'INSERT INTO {{item}}([[name]], [[category_id]]) VALUES (\'invalid\', 99999)';
        $command = $db->createCommand($sql);
        $command->execute();
        $db->createCommand()->checkIntegrity('public', 'item', true)->execute();
        $this->expectException(IntegrityException::class);
        $command->execute();
    }

    /**
     * @throws Exception
     */
    public function testCommentColumn(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $expected = "COMMENT ON COLUMN [[comment]].[[text]] IS 'This is my column.'";
        $sql = $qb->addCommentOnColumn('comment', 'text', 'This is my column.');
        $this->assertEquals($this->replaceQuotes($expected), $sql);

        $expected = 'COMMENT ON COLUMN [[comment]].[[text]] IS NULL';
        $sql = $qb->dropCommentFromColumn('comment', 'text');
        $this->assertEquals($this->replaceQuotes($expected), $sql);
    }

    /**
     * @throws Exception
     */
    public function testCommentTable(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $expected = "COMMENT ON TABLE [[comment]] IS 'This is my table.'";
        $sql = $qb->addCommentOnTable('comment', 'This is my table.');
        $this->assertEquals($this->replaceQuotes($expected), $sql);

        $expected = 'COMMENT ON TABLE [[comment]] IS NULL';
        $sql = $qb->dropCommentFromTable('comment');
        $this->assertEquals($this->replaceQuotes($expected), $sql);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::createDropIndexesProvider
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testCreateDropIndex(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    /**
     * @dataProvider createIndexesProvider
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testCreateIndex(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    public function createIndexesProvider(): array
    {
        $tableName = 'T_constraints_2';
        $name1 = 'CN_constraints_2_single';

        return [
            'create unique' => [
                "CREATE UNIQUE INDEX [[$name1]] ON {{{$tableName}}} ([[C_index_1]])",
                static function (QueryBuilderInterface $qb) use ($tableName, $name1) {
                    return $qb->createIndex($name1, $tableName, 'C_index_1', QueryBuilder::INDEX_UNIQUE);
                },
            ],
            'create simple' => [
                "CREATE INDEX [[$name1]] ON {{{$tableName}}} ([[C_index_1]])",
                static function (QueryBuilderInterface $qb) use ($tableName, $name1) {
                    return $qb->createIndex($name1, $tableName, 'C_index_1');
                },
            ],
            'create btree' => [
                "CREATE INDEX [[$name1]] ON {{{$tableName}}} USING btree ([[C_index_1]])",
                static function (QueryBuilderInterface $qb) use ($tableName, $name1) {
                    return $qb->createIndex($name1, $tableName, 'C_index_1', null, QueryBuilder::INDEX_B_TREE);
                },
            ],
        ];
    }

    public function testDropIndex(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $expected = 'DROP INDEX "index"';
        $sql = $qb->dropIndex('index', '{{table}}');
        $this->assertEquals($expected, $sql);

        $expected = 'DROP INDEX "schema"."index"';
        $sql = $qb->dropIndex('index', '{{schema.table}}');
        $this->assertEquals($expected, $sql);

        $expected = 'DROP INDEX "schema"."index"';
        $sql = $qb->dropIndex('schema.index', '{{schema2.table}}');
        $this->assertEquals($expected, $sql);

        $expected = 'DROP INDEX "schema"."index"';
        $sql = $qb->dropIndex('index', '{{schema.%table}}');
        $this->assertEquals($expected, $sql);

        $expected = 'DROP INDEX {{%schema.index}}';
        $sql = $qb->dropIndex('index', '{{%schema.table}}');
        $this->assertEquals($expected, $sql);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::deleteProvider
     *
     * @param string $table
     * @param array|string $condition
     * @param string $expectedSQL
     * @param array $expectedParams
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testDelete(string $table, array|string $condition, string $expectedSQL, array $expectedParams): void
    {
        $db = $this->getConnection();
        $actualParams = [];
        $actualSQL = $db->getQueryBuilder()->delete($table, $condition, $actualParams);
        $this->assertSame($expectedSQL, $actualSQL);
        $this->assertSame($expectedParams, $actualParams);
    }

    /**
     * @throws Exception|NotSupportedException
     */
    public function testResetSequence(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $expected = "SELECT SETVAL('\"item_id_seq\"',(SELECT COALESCE(MAX(\"id\"),0) FROM \"item\")+1,false)";
        $sql = $qb->resetSequence('item');
        $this->assertEquals($expected, $sql);

        $expected = "SELECT SETVAL('\"item_id_seq\"',4,false)";
        $sql = $qb->resetSequence('item', 4);
        $this->assertEquals($expected, $sql);
    }

    /**
     * @throws Exception|NotSupportedException
     */
    public function testResetSequencePostgres12(): void
    {
        if (version_compare($this->getConnection()->getServerVersion(), '12.0', '<')) {
            $this->markTestSkipped('PostgreSQL < 12.0 does not support GENERATED AS IDENTITY columns.');
        }

        $db = $this->getConnection(true, null, __DIR__ . '/Fixture/postgres12.sql');
        $qb = $db->getQueryBuilder();

        $expected = "SELECT SETVAL('\"item_12_id_seq\"',(SELECT COALESCE(MAX(\"id\"),0) FROM \"item_12\")+1,false)";
        $sql = $qb->resetSequence('item_12');
        $this->assertEquals($expected, $sql);

        $expected = "SELECT SETVAL('\"item_12_id_seq\"',4,false)";
        $sql = $qb->resetSequence('item_12', 4);
        $this->assertEquals($expected, $sql);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::insertProvider
     *
     * @param string $table
     * @param array|QueryInterface $columns
     * @param array $params
     * @param string $expectedSQL
     * @param array $expectedParams
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testInsert(string $table, $columns, array $params, string $expectedSQL, array $expectedParams): void
    {
        $actualParams = $params;
        $db = $this->getConnection();
        $this->assertSame($expectedSQL, $db->getQueryBuilder()->insert($table, $columns, $actualParams));
        $this->assertSame($expectedParams, $actualParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::updateProvider
     *
     * @param string $table
     * @param array $columns
     * @param array|string $condition
     * @param string $expectedSQL
     * @param array $expectedParams
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testUpdate(
        string $table,
        array $columns,
        $condition,
        string $expectedSQL,
        array $expectedParams
    ): void {
        $actualParams = [];
        $db = $this->getConnection();
        $actualSQL = $db->getQueryBuilder()->update($table, $columns, $condition, $actualParams);
        $this->assertSame($expectedSQL, $actualSQL);
        $this->assertSame($expectedParams, $actualParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QueryBuilderProvider::upsertProvider
     *
     * @param string $table
     * @param array|QueryInterface $insertColumns
     * @param array|bool $updateColumns
     * @param string|string[] $expectedSQL
     * @param array $expectedParams
     *
     * @throws Exception|NotSupportedException
     */
    public function testUpsert(string $table, $insertColumns, $updateColumns, $expectedSQL, array $expectedParams): void
    {
        $actualParams = [];
        $db = $this->getConnection();
        $actualSQL = $db->getQueryBuilder()->upsert($table, $insertColumns, $updateColumns, $actualParams);

        if (is_string($expectedSQL)) {
            $this->assertSame($expectedSQL, $actualSQL);
        } else {
            $this->assertContains($actualSQL, $expectedSQL);
        }

        if (ArrayHelper::isAssociative($expectedParams)) {
            $this->assertSame($expectedParams, $actualParams);
        } else {
            $this->assertIsOneOf($actualParams, $expectedParams);
        }
    }
}
