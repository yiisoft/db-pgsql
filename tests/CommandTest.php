<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Query\Query;

use Yiisoft\Db\TestSupport\TestCommandTrait;

use function serialize;

/**
 * @group pgsql
 */
final class CommandTest extends TestCase
{
    use TestCommandTrait;

    protected string $upsertTestCharCast = 'CAST([[address]] AS VARCHAR(255))';

    public function testAddDropCheck(): void
    {
        $db = $this->getConnection();

        $tableName = 'test_ck';
        $name = 'test_ck_constraint';

        $schema = $db->getSchema();

        if ($schema->getTableSchema($tableName) !== null) {
            $db->createCommand()->dropTable($tableName)->execute();
        }

        $db->createCommand()->createTable($tableName, [
            'int1' => 'integer',
        ])->execute();

        $this->assertEmpty($schema->getTableChecks($tableName, true));

        $db->createCommand()->addCheck($name, $tableName, '[[int1]] > 1')->execute();

        $this->assertMatchesRegularExpression(
            '/^.*int1.*>.*1.*$/',
            $schema->getTableChecks($tableName, true)[0]->getExpression()
        );

        $db->createCommand()->dropCheck($name, $tableName)->execute();

        $this->assertEmpty($schema->getTableChecks($tableName, true));
    }

    public function testAddDropPrimaryKey(): void
    {
        $db = $this->getConnection();

        $tableName = 'test_pk';
        $name = 'test_pk_constraint';

        $schema = $db->getSchema();

        if ($schema->getTableSchema($tableName) !== null) {
            $db->createCommand()->dropTable($tableName)->execute();
        }

        $db->createCommand()->createTable($tableName, [
            'int1' => 'integer not null',
            'int2' => 'integer not null',
        ])->execute();

        $this->assertNull($schema->getTablePrimaryKey($tableName, true));

        $db->createCommand()->addPrimaryKey($name, $tableName, ['int1'])->execute();
        $pk = $schema->getTablePrimaryKey($tableName, true);

        $this->assertNotNull($pk);
        $this->assertEquals(['int1'], $pk->getColumnNames());

        $db->createCommand()->dropPrimaryKey($name, $tableName)->execute();

        $this->assertNull($schema->getTablePrimaryKey($tableName, true));

        $db->createCommand()->addPrimaryKey($name, $tableName, ['int1', 'int2'])->execute();
        $pk = $schema->getTablePrimaryKey($tableName, true);

        $this->assertNotNull($pk);
        $this->assertEquals(['int1', 'int2'], $pk->getColumnNames());
    }

    public function testAutoQuoting(): void
    {
        $db = $this->getConnection();

        $sql = 'SELECT [[id]], [[t.name]] FROM {{customer}} t';

        $command = $db->createCommand($sql);

        $this->assertEquals('SELECT "id", "t"."name" FROM "customer" t', $command->getSql());
    }

    public function testBooleanValuesInsert(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $command->insert('bool_values', ['bool_col' => true]);

        $this->assertEquals(1, $command->execute());

        $command = $db->createCommand();

        $command->insert('bool_values', ['bool_col' => false]);

        $this->assertEquals(1, $command->execute());

        $command = $db->createCommand('SELECT COUNT(*) FROM "bool_values" WHERE bool_col = TRUE;');

        $this->assertEquals(1, $command->queryScalar());

        $command = $db->createCommand('SELECT COUNT(*) FROM "bool_values" WHERE bool_col = FALSE;');

        $this->assertEquals(1, $command->queryScalar());
    }

    public function testBooleanValuesBatchInsert(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $command->batchInsert('bool_values', ['bool_col'], [[true], [false]]);

        $this->assertEquals(2, $command->execute());

        $command = $db->createCommand('SELECT COUNT(*) FROM "bool_values" WHERE bool_col = TRUE;');

        $this->assertEquals(1, $command->queryScalar());

        $command = $db->createCommand('SELECT COUNT(*) FROM "bool_values" WHERE bool_col = FALSE;');

        $this->assertEquals(1, $command->queryScalar());
    }

    public function testLastInsertId(): void
    {
        $db = $this->getConnection();

        $sql = 'INSERT INTO {{profile}}([[description]]) VALUES (\'non duplicate\')';

        $command = $db->createCommand($sql);

        $command->execute();

        $this->assertEquals(3, $db->getLastInsertID('public.profile_id_seq'));

        $sql = 'INSERT INTO {{schema1.profile}}([[description]]) VALUES (\'non duplicate\')';

        $command = $db->createCommand($sql);

        $command->execute();

        $this->assertEquals(3, $db->getLastInsertID('schema1.profile_id_seq'));
    }

    public function testLastInsertIdException(): void
    {
        $db = $this->getConnection();
        $db->close();

        $this->expectException(InvalidCallException::class);
        $db->getLastInsertID('schema1.profile_id_seq');
    }

    public function testLastInsertIdNotSupportedException(): void
    {
        $db = $this->getConnection();

        $this->expectException(InvalidArgumentException::class);
        $db->getLastInsertID();
    }

    /**
     * {@see https://github.com/yiisoft/yii2/issues/11498}
     */
    public function testSaveSerializedObject(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand()->insert('type', [
            'int_col' => 1,
            'char_col' => 'serialize',
            'float_col' => 5.6,
            'bool_col' => true,
            'blob_col' => serialize($db),
        ]);

        $this->assertEquals(1, $command->execute());

        $command = $db->createCommand()->update('type', [
            'blob_col' => serialize($db),
        ], ['char_col' => 'serialize']);

        $this->assertEquals(1, $command->execute());
    }

    /**
     * {@see https://github.com/yiisoft/yii2/issues/15827}
     */
    public function testIssue15827(): void
    {
        $db = $this->getConnection();

        $inserted = $db->createCommand()->insert('array_and_json_types', [
            'jsonb_col' => new JsonExpression(['Solution date' => '13.01.2011']),
        ])->execute();

        $this->assertSame(1, $inserted);

        $found = $db->createCommand(
            <<<PGSQL
            SELECT *
            FROM array_and_json_types
            WHERE jsonb_col @> '{"Some not existing key": "random value"}'
PGSQL
        )->execute();

        $this->assertSame(0, $found);

        $found = $db->createCommand(
            <<<PGSQL
            SELECT *
            FROM array_and_json_types
            WHERE jsonb_col @> '{"Solution date": "13.01.2011"}'
PGSQL
        )->execute();

        $this->assertSame(1, $found);
        $this->assertSame(1, $db->createCommand()->delete('array_and_json_types')->execute());
    }

    public function batchInsertSqlProvider(): array
    {
        $data = $this->batchInsertSqlProviderTrait();

        // @todo: need discuss about normalizing field names before using
        unset($data['wrongBehavior']);

        $data['batchInsert binds params from jsonExpression'] = [
            '{{%type}}',
            ['json_col', 'int_col', 'float_col', 'char_col', 'bool_col'],
            [[new JsonExpression(
                ['username' => 'silverfire', 'is_active' => true, 'langs' => ['Ukrainian', 'Russian', 'English']]
            ), 1, 1, '', false]],
            'expected' => 'INSERT INTO "type" ("json_col", "int_col", "float_col", "char_col", "bool_col") VALUES (:qp0, :qp1, :qp2, :qp3, :qp4)',
            'expectedParams' => [
                ':qp0' => '{"username":"silverfire","is_active":true,"langs":["Ukrainian","Russian","English"]}',
                ':qp1' => 1,
                ':qp2' => 1.0,
                ':qp3' => '',
                ':qp4' => false,
            ],
        ];

        $data['batchInsert binds params from arrayExpression'] = [
            '{{%type}}',
            ['intarray_col', 'int_col', 'float_col', 'char_col', 'bool_col'],
            [[new ArrayExpression([1,null,3], 'int'), 1, 1, '', false]],
            'expected' => 'INSERT INTO "type" ("intarray_col", "int_col", "float_col", "char_col", "bool_col") VALUES (ARRAY[:qp0, :qp1, :qp2]::int[], :qp3, :qp4, :qp5, :qp6)',
            'expectedParams' => [':qp0' => 1, ':qp1' => null, ':qp2' => 3, ':qp3' => 1, ':qp4' => 1.0, ':qp5' => '', ':qp6' => false],
        ];

        $data['batchInsert casts string to int according to the table schema'] = [
            '{{%type}}',
            ['int_col', 'float_col', 'char_col', 'bool_col'],
            [['3', '1.1', '', false]],
            'expected' => 'INSERT INTO "type" ("int_col", "float_col", "char_col", "bool_col") VALUES (:qp0, :qp1, :qp2, :qp3)',
            'expectedParams' => [
                ':qp0' => 3,
                ':qp1' => 1.1,
                ':qp2' => '',
                ':qp3' => false,
            ],
        ];

        $data['batchInsert binds params from jsonbExpression'] = [
            '{{%type}}',
            ['jsonb_col', 'int_col', 'float_col', 'char_col', 'bool_col'],
            [[new JsonExpression(
                ['a' => true]
            ), 1, 1.1, '', false]],
            'expected' => 'INSERT INTO "type" ("jsonb_col", "int_col", "float_col", "char_col", "bool_col") VALUES (:qp0, :qp1, :qp2, :qp3, :qp4)',
            'expectedParams' => [
                ':qp0' => '{"a":true}',
                ':qp1' => 1,
                ':qp2' => 1.1,
                ':qp3' => '',
                ':qp4' => false,
            ],
        ];

        return $data;
    }

    /**
     * Make sure that `{{something}}` in values will not be encoded.
     *
     * @dataProvider batchInsertSqlProvider
     *
     * @param string $table
     * @param array $columns
     * @param array $values
     * @param string $expected
     * @param array $expectedParams
     * @param int $insertedRow
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * {@see https://github.com/yiisoft/yii2/issues/11242}
     */
    public function testBatchInsertSQL(
        string $table,
        array $columns,
        array $values,
        string $expected,
        array $expectedParams = [],
        int $insertedRow = 1
    ): void {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $command->batchInsert($table, $columns, $values);

        $command->prepare(false);

        $this->assertSame($expected, $command->getSql());
        $this->assertSame($expectedParams, $command->getParams());

        $command->execute();
        $this->assertEquals($insertedRow, (new Query($db))->from($table)->count());
    }

    /**
     * Test whether param binding works in other places than WHERE.
     *
     * @dataProvider bindParamsNonWhereProviderTrait
     *
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testBindParamsNonWhere(string $sql): void
    {
        $db = $this->getConnection();

        $db->createCommand()->insert(
            'customer',
            [
                'name' => 'testParams',
                'email' => 'testParams@example.com',
                'address' => '1',
            ]
        )->execute();

        $params = [
            ':email' => 'testParams@example.com',
            ':len' => 5,
        ];

        $command = $db->createCommand($sql, $params);

        $this->assertEquals('Params', $command->queryScalar());
    }

    /**
     * Test command getRawSql.
     *
     * @dataProvider getRawSqlProviderTrait
     *
     * @param string $sql
     * @param array $params
     * @param string $expectedRawSql
     *
     * {@see https://github.com/yiisoft/yii2/issues/8592}
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testGetRawSql(string $sql, array $params, string $expectedRawSql): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand($sql, $params);

        $this->assertEquals($expectedRawSql, $command->getRawSql());
    }

    /**
     * Test INSERT INTO ... SELECT SQL statement with wrong query object.
     *
     * @dataProvider invalidSelectColumnsProviderTrait
     *
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testInsertSelectFailed(mixed $invalidSelectColumns): void
    {
        $db = $this->getConnection();

        $query = new Query($db);

        $query->select($invalidSelectColumns)->from('{{customer}}');

        $command = $db->createCommand();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected select query object with enumerated (named) parameters');

        $command->insert(
            '{{customer}}',
            $query
        )->execute();
    }

    /**
     * Test command upsert.
     *
     * @dataProvider upsertProviderTrait
     *
     *
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testUpsert(array $firstData, array $secondData): void
    {
        $db = $this->getConnection(true);
        $this->assertEquals(0, $db->createCommand('SELECT COUNT(*) FROM {{T_upsert}}')->queryScalar());
        $this->performAndCompareUpsertResult($db, $firstData);
        $this->assertEquals(1, $db->createCommand('SELECT COUNT(*) FROM {{T_upsert}}')->queryScalar());
        $this->performAndCompareUpsertResult($db, $secondData);
    }
}
