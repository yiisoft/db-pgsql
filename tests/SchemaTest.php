<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use JsonException;
use Throwable;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Pgsql\Schema;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Schema\TableSchemaInterface;
use Yiisoft\Db\Tests\Common\CommonSchemaTest;
use Yiisoft\Db\Tests\Support\DbHelper;

/**
 * @group pgsql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class SchemaTest extends CommonSchemaTest
{
    use TestTrait;

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testBooleanDefaultValues(): void
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $table = $schema->getTableSchema('bool_values');

        $this->assertNotNull($table);

        $columnTrue = $table->getColumn('default_true');
        $columnFalse = $table->getColumn('default_false');

        $this->assertNotNull($columnTrue);
        $this->assertNotNull($columnFalse);
        $this->assertTrue($columnTrue->getDefaultValue());
        $this->assertFalse($columnFalse->getDefaultValue());

        $db->close();
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\SchemaProvider::columns()
     *
     * @throws Exception
     */
    public function testColumnSchema(array $columns, string $tableName): void
    {
        $db = $this->getConnection();

        if (version_compare($db->getServerVersion(), '10', '>')) {
            if ($tableName === 'type') {
                $columns['ts_default']['defaultValue'] = new Expression('CURRENT_TIMESTAMP');
            }
        }

        $this->columnSchema($columns, $tableName);

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testColumnSchemaTypeMapNoExist(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('type_range') !== null) {
            $command->dropTable('type_range')->execute();
        }

        $command->createTable('type_range', ['id' => 'int', 'during tsrange'])->execute();

        $table = $schema->getTableSchema('type_range', true);

        $this->assertNotNull($table);
        $this->assertNotNull($table->getColumn('during'));
        $this->assertSame('string', $table->getColumn('during')?->getType());

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testGeneratedValues(): void
    {
        $this->fixture = 'pgsql12.sql';

        if (version_compare($this->getConnection()->getServerVersion(), '12.0', '<')) {
            $this->markTestSkipped('PostgresSQL < 12.0 does not support GENERATED AS IDENTITY columns.');
        }

        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $table = $schema->getTableSchema('generated');

        $this->assertNotNull($table);
        $this->assertTrue($table->getColumn('id_always')?->isAutoIncrement());
        $this->assertTrue($table->getColumn('id_primary')?->isAutoIncrement());
        $this->assertTrue($table->getColumn('id_primary')?->isAutoIncrement());
        $this->assertTrue($table->getColumn('id_default')?->isAutoIncrement());

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testGetDefaultSchema(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->assertSame('public', $schema->getDefaultSchema());

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testGetSchemaDefaultValues(): void
    {
        $db = $this->getConnection();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Pgsql\Schema::loadTableDefaultValues is not supported by PostgreSQL.'
        );

        $db->getSchema()->getSchemaDefaultValues();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testGetSchemaNames(): void
    {
        $db = $this->getConnection(true);

        $expectedSchemas = ['public', 'schema1', 'schema2'];
        $schema = $db->getSchema();
        $schemas = $schema->getSchemaNames();

        $this->assertNotEmpty($schemas);

        foreach ($expectedSchemas as $schema) {
            $this->assertContains($schema, $schemas);
        }

        $db->close();
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\SchemaProvider::columnsTypeChar()
     */
    public function testGetStringFieldsSize(
        string $columnName,
        string $columnType,
        int|null $columnSize,
        string $columnDbType
    ): void {
        parent::testGetStringFieldsSize($columnName, $columnType, $columnSize, $columnDbType);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testGetTableSchemasNotSchemaDefault(): void
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $tables = $schema->getTableSchemas('schema1');

        $this->assertCount(count($schema->getTableNames('schema1')), $tables);

        foreach ($tables as $table) {
            $this->assertInstanceOf(TableSchemaInterface::class, $table);
        }

        $db->close();
    }

    /**
     * @link https://github.com/yiisoft/yii2/issues/12483
     *
     * @throws Exception
     * @throws Throwable
     */
    public function testParenthesisDefaultValue(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('test_default_parenthesis') !== null) {
            $command->dropTable('test_default_parenthesis')->execute();
        }

        $command->createTable(
            'test_default_parenthesis',
            [
                'id' => 'pk',
                'user_timezone' => 'numeric(5,2) DEFAULT (0)::numeric NOT NULL',
            ],
        )->execute();

        $schema->refreshTableSchema('test_default_parenthesis');
        $tableSchema = $schema->getTableSchema('test_default_parenthesis');

        $this->assertNotNull($tableSchema);

        $column = $tableSchema->getColumn('user_timezone');

        $this->assertNotNull($column);
        $this->assertFalse($column->isAllowNull());
        $this->assertEquals('numeric', $column->getDbType());
        $this->assertEquals(0, $column->getDefaultValue());

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testPartitionedTable(): void
    {
        $this->fixture = 'pgsql10.sql';

        if (version_compare($this->getConnection()->getServerVersion(), '10.0', '<')) {
            $this->markTestSkipped('PostgresSQL < 10.0 does not support PARTITION BY clause.');
        }

        $db = $this->getConnection(true);

        $schema = $db->getSchema();

        $this->assertNotNull($schema->getTableSchema('partitioned'));

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testSequenceName(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('item');

        $this->assertNotNull($tableSchema);

        $sequenceName = $tableSchema->getSequenceName();
        $command->setSql(
            <<<SQL
            ALTER TABLE "item" ALTER COLUMN "id" SET DEFAULT nextval('item_id_seq_2')
            SQL,
        )->execute();
        $schema->refreshTableSchema('item');
        $tableSchema = $schema->getTableSchema('item');

        $this->assertNotNull($tableSchema);
        $this->assertEquals('item_id_seq_2', $tableSchema->getSequenceName());

        $command->setSql(
            <<<SQL
            ALTER TABLE "item" ALTER COLUMN "id" SET DEFAULT nextval('$sequenceName')
            SQL,
        )->execute();
        $schema->refreshTableSchema('item');
        $tableSchema = $schema->getTableSchema('item');

        $this->assertNotNull($tableSchema);
        $this->assertEquals($sequenceName, $tableSchema->getSequenceName());

        $db->close();
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\SchemaProvider::tableSchemaCacheWithTablePrefixes()
     *
     * @throws Exception
     */
    public function testTableSchemaCacheWithTablePrefixes(
        string $tablePrefix,
        string $tableName,
        string $testTablePrefix,
        string $testTableName
    ): void {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        $schema->schemaCacheEnable(true);
        $db->setTablePrefix($tablePrefix);
        $noCacheTable = $schema->getTableSchema($tableName, true);

        $this->assertInstanceOf(TableSchemaInterface::class, $noCacheTable);

        /* Compare */
        $db->setTablePrefix($testTablePrefix);
        $testNoCacheTable = $schema->getTableSchema($testTableName);

        $this->assertSame($noCacheTable, $testNoCacheTable);

        $db->setTablePrefix($tablePrefix);
        $schema->refreshTableSchema($tableName);
        $refreshedTable = $schema->getTableSchema($tableName);

        $this->assertInstanceOf(TableSchemaInterface::class, $refreshedTable);
        $this->assertNotSame($noCacheTable, $refreshedTable);

        /* Compare */
        $db->setTablePrefix($testTablePrefix);
        $schema->refreshTableSchema($testTablePrefix);
        $testRefreshedTable = $schema->getTableSchema($testTableName);

        $this->assertInstanceOf(TableSchemaInterface::class, $testRefreshedTable);
        $this->assertSame($refreshedTable, $testRefreshedTable);
        $this->assertNotSame($testNoCacheTable, $testRefreshedTable);

        $db->close();
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\SchemaProvider::constraints()
     *
     * @throws Exception
     * @throws JsonException
     */
    public function testTableSchemaConstraints(string $tableName, string $type, mixed $expected): void
    {
        parent::testTableSchemaConstraints($tableName, $type, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\SchemaProvider::constraints()
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws JsonException
     * @throws NotSupportedException
     */
    public function testTableSchemaConstraintsWithPdoLowercase(string $tableName, string $type, mixed $expected): void
    {
        parent::testTableSchemaConstraintsWithPdoLowercase($tableName, $type, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\SchemaProvider::constraints()
     *
     * @throws Exception
     * @throws JsonException
     */
    public function testTableSchemaConstraintsWithPdoUppercase(string $tableName, string $type, mixed $expected): void
    {
        parent::testTableSchemaConstraintsWithPdoUppercase($tableName, $type, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\SchemaProvider::tableSchemaWithDbSchemes()
     *
     * @throws Exception
     */
    public function testTableSchemaWithDbSchemes(
        string $tableName,
        string $expectedTableName,
        string $expectedSchemaName = ''
    ): void {
        $db = $this->getConnection();

        $commandMock = $this->createMock(CommandInterface::class);
        $commandMock->method('queryAll')->willReturn([]);
        $mockDb = $this->createMock(ConnectionInterface::class);
        $mockDb->method('getQuoter')->willReturn($db->getQuoter());
        $mockDb
            ->expects(self::atLeastOnce())
            ->method('createCommand')
            ->with(
                self::callback(static fn ($sql) => true),
                self::callback(
                    function ($params) use ($expectedTableName, $expectedSchemaName) {
                        $this->assertSame($expectedTableName, $params[':tableName']);
                        $this->assertSame($expectedSchemaName, $params[':schemaName']);

                        return true;
                    }
                )
            )
            ->willReturn($commandMock);
        $schema = new Schema($mockDb, DbHelper::getSchemaCache());
        $schema->getTableSchema($tableName);

        $db->close();
    }

    /**
     * @link https://github.com/yiisoft/yii2/issues/14192
     *
     * @throws Exception
     * @throws Throwable
     */
    public function testTimestampNullDefaultValue(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('test_timestamp_default_null') !== null) {
            $command->dropTable('test_timestamp_default_null')->execute();
        }

        $command->createTable(
            'test_timestamp_default_null',
            ['id' => 'pk', 'timestamp' => 'timestamp DEFAULT NULL']
        )->execute();
        $schema->refreshTableSchema('test_timestamp_default_null');
        $tableSchema = $schema->getTableSchema('test_timestamp_default_null');

        $this->assertNotNull($tableSchema);

        $columnSchema = $tableSchema->getColumn('timestamp');

        $this->assertNotNull($columnSchema);
        $this->assertNull($columnSchema->getDefaultValue());

        $db->close();
    }

    public function testWorkWithDefaultValueConstraint(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Pgsql\DDLQueryBuilder::addDefaultValue is not supported by PostgreSQL.'
        );

        parent::testWorkWithDefaultValueConstraint();
    }

    public function withIndexDataProvider(): array
    {
        return array_merge(parent::withIndexDataProvider(), [
            [
                'indexType' => null,
                'indexMethod' => SchemaInterface::INDEX_BTREE,
                'columnType' => 'varchar(16)',
            ],
            [
                'indexType' => null,
                'indexMethod' => SchemaInterface::INDEX_HASH,
                'columnType' => 'varchar(16)',
            ],
            [
                'indexType' => null,
                'indexMethod' => SchemaInterface::INDEX_BRIN,
                'columnType' => 'varchar(16)',
            ],
            [
                'indexType' => null,
                'indexMethod' => SchemaInterface::INDEX_GIN,
                'columnType' => 'jsonb',
            ],
            [
                'indexType' => null,
                'indexMethod' => SchemaInterface::INDEX_GIST,
                'columnType' => 'tsvector',
            ],
        ]);
    }

    public function testCustomTypeInNonDefaultSchema()
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema()->getTableSchema('schema2.custom_type_test_table');
        $this->assertEquals('my_type', $schema->getColumn('test_type')->getDbType());
        $this->assertEquals('schema2.my_type2', $schema->getColumn('test_type2')->getDbType());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testDefaultValueDatetimeColumn(): void
    {
        $tableName = '{{%datetime_test}}';
        $db = $this->getConnection();

        $columnsData = [
            'id' => ['int NOT NULL PRIMARY KEY', '', false],
            'd' => ['date DEFAULT \'2011-11-11\'', '2011-11-11', false],
            'dt' => ['datetime NOT NULL DEFAULT CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP', true],
            'dt1' => ['datetime DEFAULT \'2011-11-11 00:00:00\'', '2011-11-11 00:00:00', false],
            'dt2' => ['datetime DEFAULT CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP', true],
            'ts1' => ['timestamp DEFAULT \'2011-11-11 00:00:00\'', '2011-11-11 00:00:00', false],
            'ts2' => ['timestamp DEFAULT CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP', true],
            'ts4' => ['date DEFAULT (CURRENT_DATE + INTERVAL \'2 YEAR\')', 'CURRENT_DATE + \'2 years\'::interval', true],
            'simple_int' => ['int DEFAULT \'0\'', '0', false],
            'simple_col' => ['varchar(40) DEFAULT \'uuid()\'', 'uuid()', false],
            'uuid_col' => ['varchar(40) DEFAULT md5(\'abc\')', 'md5(\'abc\'::text)', true],
            'uuid_col2' => ['varchar(40) DEFAULT md5(\'abc\')::varchar', 'md5(\'abc\'::text)', true],
        ];
        $columns = [];
        foreach ($columnsData as $column => $columnData) {
            $columns[$column] = $columnData[0];
        }

        if ($db->getTableSchema($tableName) !== null) {
            $db->createCommand()->dropTable($tableName)->execute();
        }

        $db->createCommand()->createTable($tableName, $columns)->execute();

        $tableSchema = $db->getTableSchema($tableName, true);
        $this->assertNotNull($tableSchema);

        foreach ($tableSchema->getColumns() as $column) {
            $columnName = $column->getName();
            if ($columnsData[$columnName][2]) {
                $this->assertInstanceOf(Expression::class, $column->getDefaultValue(), $columnName);
            } else {
                $this->assertNotInstanceOf(Expression::class, $column->getDefaultValue(), $columnName);
            }
            $this->assertSame($columnsData[$columnName][1], (string) $column->getDefaultValue());
        }
    }
}
