<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constraint\Index;
use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Pgsql\Schema;
use Yiisoft\Db\Pgsql\Tests\Provider\SchemaProvider;
use Yiisoft\Db\Pgsql\Tests\Provider\StructuredTypeProvider;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Schema\TableSchemaInterface;
use Yiisoft\Db\Tests\Common\CommonSchemaTest;
use Yiisoft\Db\Tests\Support\DbHelper;

/**
 * @group pgsql
 */
final class SchemaTest extends CommonSchemaTest
{
    use TestTrait;

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

    #[DataProviderExternal(SchemaProvider::class, 'columns')]
    public function testColumns(array $columns, string $tableName): void
    {
        $db = $this->getConnection();

        if (version_compare($db->getServerInfo()->getVersion(), '10', '>')) {
            if ($tableName === 'type') {
                $columns['timestamp_default']->defaultValue(new Expression('CURRENT_TIMESTAMP'));
            }
        }

        $this->assertTableColumns($columns, $tableName);

        $db->close();
    }

    public function testColumnTypeMapNoExist(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('type_range') !== null) {
            $command->dropTable('type_range')->execute();
        }

        $command->createTable('type_range', ['id' => 'int', 'during point'])->execute();

        $table = $schema->getTableSchema('type_range', true);

        $this->assertNotNull($table);
        $this->assertNotNull($table->getColumn('during'));
        $this->assertSame('string', $table->getColumn('during')?->getType());

        $db->close();
    }

    public function testGeneratedValues(): void
    {
        $this->fixture = 'pgsql12.sql';

        if (version_compare($this->getConnection()->getServerInfo()->getVersion(), '12.0', '<')) {
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

    public function testGetDefaultSchema(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->assertSame('public', $schema->getDefaultSchema());

        $db->close();
    }

    public function testGetSchemaDefaultValues(): void
    {
        $db = $this->getConnection();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Pgsql\Schema::loadTableDefaultValues is not supported by PostgreSQL.',
        );

        $db->getSchema()->getSchemaDefaultValues();
    }

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
        $this->assertTrue($column->isNotNull());
        $this->assertEquals('numeric', $column->getDbType());
        $this->assertEquals(0, $column->getDefaultValue());

        $db->close();
    }

    public function testPartitionedTable(): void
    {
        $this->fixture = 'pgsql10.sql';

        if (version_compare($this->getConnection()->getServerInfo()->getVersion(), '10.0', '<')) {
            $this->markTestSkipped('PostgresSQL < 10.0 does not support PARTITION BY clause.');
        }

        $db = $this->getConnection(true);

        $schema = $db->getSchema();

        $this->assertNotNull($schema->getTableSchema('partitioned'));

        $db->close();
    }

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
            ALTER TABLE "item" ALTER COLUMN "id" SET DEFAULT nextval('nextval_item_id_seq_2')
            SQL,
        )->execute();
        $schema->refreshTableSchema('item');
        $tableSchema = $schema->getTableSchema('item');

        $this->assertNotNull($tableSchema);
        $this->assertEquals('nextval_item_id_seq_2', $tableSchema->getSequenceName());

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

    #[DataProviderExternal(SchemaProvider::class, 'tableSchemaCacheWithTablePrefixes')]
    public function testTableSchemaCacheWithTablePrefixes(
        string $tablePrefix,
        string $tableName,
        string $testTablePrefix,
        string $testTableName,
    ): void {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        $schema->enableCache(true);
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

    #[DataProviderExternal(SchemaProvider::class, 'constraints')]
    #[DataProviderExternal(SchemaProvider::class, 'constraintsOfView')]
    public function testTableSchemaConstraints(string $tableName, string $type, mixed $expected): void
    {
        parent::testTableSchemaConstraints($tableName, $type, $expected);
    }

    #[DataProviderExternal(SchemaProvider::class, 'constraints')]
    public function testTableSchemaConstraintsWithPdoLowercase(string $tableName, string $type, mixed $expected): void
    {
        parent::testTableSchemaConstraintsWithPdoLowercase($tableName, $type, $expected);
    }

    #[DataProviderExternal(SchemaProvider::class, 'constraints')]
    public function testTableSchemaConstraintsWithPdoUppercase(string $tableName, string $type, mixed $expected): void
    {
        parent::testTableSchemaConstraintsWithPdoUppercase($tableName, $type, $expected);
    }

    #[DataProviderExternal(SchemaProvider::class, 'tableSchemaWithDbSchemes')]
    public function testTableSchemaWithDbSchemes(
        string $tableName,
        string $expectedTableName,
        string $expectedSchemaName = '',
    ): void {
        $db = $this->getConnection();

        $commandMock = $this->createMock(CommandInterface::class);
        $commandMock->method('queryAll')->willReturn([]);
        $mockDb = $this->createMock(PdoConnectionInterface::class);
        $mockDb->method('getQuoter')->willReturn($db->getQuoter());
        $mockDb
            ->expects(self::atLeastOnce())
            ->method('createCommand')
            ->with(
                self::callback(static fn($sql) => true),
                self::callback(
                    function ($params) use ($expectedTableName, $expectedSchemaName) {
                        $this->assertSame($expectedTableName, $params[':tableName']);
                        $this->assertSame($expectedSchemaName, $params[':schemaName']);

                        return true;
                    },
                ),
            )
            ->willReturn($commandMock);
        $schema = new Schema($mockDb, DbHelper::getSchemaCache());
        $schema->getTableSchema($tableName);

        $db->close();
    }

    /**
     * @link https://github.com/yiisoft/yii2/issues/14192
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
            ['id' => 'pk', 'timestamp' => 'timestamp DEFAULT NULL'],
        )->execute();
        $schema->refreshTableSchema('test_timestamp_default_null');
        $tableSchema = $schema->getTableSchema('test_timestamp_default_null');

        $this->assertNotNull($tableSchema);

        $column = $tableSchema->getColumn('timestamp');

        $this->assertNotNull($column);
        $this->assertNull($column->getDefaultValue());

        $db->close();
    }

    public function testWorkWithDefaultValueConstraint(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Pgsql\DDLQueryBuilder::addDefaultValue is not supported by PostgreSQL.',
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

        $db->close();
    }

    public function testNotConnectionPDO(): void
    {
        $db = $this->createMock(ConnectionInterface::class);
        $schema = new Schema($db, DbHelper::getSchemaCache());

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Only PDO connections are supported.');

        $schema->refresh();
    }

    public function testDomainType(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        $command->setSql('DROP DOMAIN IF EXISTS sex_char CASCADE')->execute();
        $command->setSql(
            'CREATE DOMAIN sex_char AS "char" NOT NULL DEFAULT \'x\' CHECK (VALUE in (\'m\', \'f\', \'x\'))',
        )->execute();

        if ($schema->getTableSchema('test_domain_type') !== null) {
            $command->dropTable('test_domain_type')->execute();
        }
        $command->createTable('test_domain_type', ['id' => 'pk', 'sex' => 'sex_char'])->execute();

        $schema->refreshTableSchema('test_domain_type');
        $tableSchema = $schema->getTableSchema('test_domain_type');
        $column = $tableSchema->getColumn('sex');

        $this->assertTrue($column->isNotNull());
        $this->assertEquals('char', $column->getDbType());
        $this->assertEquals('x', $column->getDefaultValue());

        $command->insert('test_domain_type', ['sex' => 'm'])->execute();
        $sex = $command->setSql('SELECT sex FROM test_domain_type')->queryScalar();
        $this->assertEquals('m', $sex);

        $db->close();
    }

    public function testGetViewNames(): void
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $views = $schema->getViewNames();

        $this->assertSame(
            [
                'T_constraints_1_view',
                'T_constraints_2_view',
                'T_constraints_3_view',
                'T_constraints_4_view',
                'animal_view',
            ],
            $views,
        );

        $db->close();
    }

    #[DataProviderExternal(StructuredTypeProvider::class, 'columns')]
    public function testStructuredTypeColumn(array $columns, string $tableName): void
    {
        $this->assertTableColumns($columns, $tableName);
    }

    public function testTableIndexes(): void
    {
        $this->fixture = 'pgsql11.sql';

        if (version_compare($this->getConnection()->getServerInfo()->getVersion(), '11.0', '<')) {
            $this->markTestSkipped('PostgresSQL < 11.0 does not support INCLUDE clause.');
        }

        $db = $this->getConnection(true);
        $schema = $db->getSchema();

        $tableIndexes = $schema->getTableIndexes('table_index');

        $this->assertEquals(
            [
                'table_index_pkey' => new Index('table_index_pkey', ['id'], true, true),
                'table_index_one_unique_key' => new Index('table_index_one_unique_key', ['one_unique'], true),
                'table_index_two_unique_1_two_unique_2_key' => new Index('table_index_two_unique_1_two_unique_2_key', ['two_unique_1', 'two_unique_2'], true),
                'table_index_unique_index_non_unique_index_idx' => new Index('table_index_unique_index_non_unique_index_idx', ['unique_index'], true),
                'table_index_non_unique_index_unique_index_idx' => new Index('table_index_non_unique_index_unique_index_idx', ['non_unique_index']),
            ],
            $tableIndexes,
        );

        $db->close();
    }

    #[DataProviderExternal(SchemaProvider::class, 'resultColumns')]
    public function testGetResultColumn(?ColumnInterface $expected, array $metadata): void
    {
        parent::testGetResultColumn($expected, $metadata);
    }
}
