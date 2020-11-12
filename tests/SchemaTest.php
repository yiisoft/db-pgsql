<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PDO;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Pgsql\TableSchema;
use Yiisoft\Db\TestUtility\AnyValue;
use Yiisoft\Db\TestUtility\TestSchemaTrait;

use function array_map;
use function fclose;
use function fopen;
use function trim;
use function ucfirst;
use function version_compare;

/**
 * @group pgsql
 */
final class SchemaTest extends TestCase
{
    use TestSchemaTrait;

    public function getExpectedColumns(): array
    {
        $version = $this->getConnection()->getServerVersion();

        return [
            'int_col' => [
                'type' => 'integer',
                'dbType' => 'int4',
                'phpType' => 'integer',
                'allowNull' => false,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => 32,
                'scale' => 0,
                'defaultValue' => null,
            ],
            'int_col2' => [
                'type' => 'integer',
                'dbType' => 'int4',
                'phpType' => 'integer',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => 32,
                'scale' => 0,
                'defaultValue' => 1,
            ],
            'tinyint_col' => [
                'type' => 'smallint',
                'dbType' => 'int2',
                'phpType' => 'integer',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => 16,
                'scale' => 0,
                'defaultValue' => 1,
            ],
            'smallint_col' => [
                'type' => 'smallint',
                'dbType' => 'int2',
                'phpType' => 'integer',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => 16,
                'scale' => 0,
                'defaultValue' => 1,
            ],
            'char_col' => [
                'type' => 'char',
                'dbType' => 'bpchar',
                'phpType' => 'string',
                'allowNull' => false,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 100,
                'precision' => null,
                'scale' => null,
                'defaultValue' => null,
            ],
            'char_col2' => [
                'type' => 'string',
                'dbType' => 'varchar',
                'phpType' => 'string',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 100,
                'precision' => null,
                'scale' => null,
                'defaultValue' => 'something',
            ],
            'char_col3' => [
                'type' => 'text',
                'dbType' => 'text',
                'phpType' => 'string',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => null,
                'scale' => null,
                'defaultValue' => null,
            ],
            'float_col' => [
                'type' => 'double',
                'dbType' => 'float8',
                'phpType' => 'double',
                'allowNull' => false,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => 53,
                'scale' => null,
                'defaultValue' => null,
            ],
            'float_col2' => [
                'type' => 'double',
                'dbType' => 'float8',
                'phpType' => 'double',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => 53,
                'scale' => null,
                'defaultValue' => 1.23,
            ],
            'blob_col' => [
                'type' => 'binary',
                'dbType' => 'bytea',
                'phpType' => 'resource',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => null,
                'scale' => null,
                'defaultValue' => null,
            ],
            'numeric_col' => [
                'type' => 'decimal',
                'dbType' => 'numeric',
                'phpType' => 'string',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => 5,
                'scale' => 2,
                'defaultValue' => '33.22',
            ],
            'time' => [
                'type' => 'timestamp',
                'dbType' => 'timestamp',
                'phpType' => 'string',
                'allowNull' => false,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => null,
                'scale' => null,
                'defaultValue' => '2002-01-01 00:00:00',
            ],
            'bool_col' => [
                'type' => 'boolean',
                'dbType' => 'bool',
                'phpType' => 'boolean',
                'allowNull' => false,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => null,
                'scale' => null,
                'defaultValue' => null,
            ],
            'bool_col2' => [
                'type' => 'boolean',
                'dbType' => 'bool',
                'phpType' => 'boolean',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => null,
                'scale' => null,
                'defaultValue' => true,
            ],
            'ts_default' => [
                'type' => 'timestamp',
                'dbType' => 'timestamp',
                'phpType' => 'string',
                'allowNull' => false,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => null,
                'scale' => null,
                'defaultValue' => version_compare($version, '10', '<')
                    ? new Expression('now()') : new Expression('CURRENT_TIMESTAMP'),
            ],
            'bit_col' => [
                'type' => 'integer',
                'dbType' => 'bit',
                'phpType' => 'integer',
                'allowNull' => false,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 8,
                'precision' => null,
                'scale' => null,
                'defaultValue' => 130 //b '10000010'
            ],
            'bigint_col' => [
                'type' => 'bigint',
                'dbType' => 'int8',
                'phpType' => 'integer',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => 64,
                'scale' => 0,
                'defaultValue' => null,
            ],
            'intarray_col' => [
                'type' => 'integer',
                'dbType' => 'int4',
                'phpType' => 'integer',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => null,
                'scale' => null,
                'defaultValue' => null,
                'dimension' => 1
            ],
            'textarray2_col' => [
                'type' => 'text',
                'dbType' => 'text',
                'phpType' => 'string',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => null,
                'scale' => null,
                'defaultValue' => null,
                'dimension' => 2
            ],
            'json_col' => [
                'type' => 'json',
                'dbType' => 'json',
                'phpType' => 'array',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => null,
                'scale' => null,
                'defaultValue' => ["a" => 1],
                'dimension' => 0
            ],
            'jsonb_col' => [
                'type' => 'json',
                'dbType' => 'jsonb',
                'phpType' => 'array',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => null,
                'scale' => null,
                'defaultValue' => null,
                'dimension' => 0
            ],
            'jsonarray_col' => [
                'type' => 'json',
                'dbType' => 'json',
                'phpType' => 'array',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => null,
                'scale' => null,
                'defaultValue' => null,
                'dimension' => 1
            ]
        ];
    }

    public function testCompositeFk(): void
    {
        $schema = $this->getConnection()->getSchema();

        $table = $schema->getTableSchema('composite_fk');

        $fk = $table->getForeignKeys();
        $this->assertCount(1, $fk);
        $this->assertTrue(isset($fk['fk_composite_fk_order_item']));
        $this->assertEquals('order_item', $fk['fk_composite_fk_order_item'][0]);
        $this->assertEquals('order_id', $fk['fk_composite_fk_order_item']['order_id']);
        $this->assertEquals('item_id', $fk['fk_composite_fk_order_item']['item_id']);
    }

    public function testGetPDOType(): void
    {
        $values = [
            [null, PDO::PARAM_NULL],
            ['', PDO::PARAM_STR],
            ['hello', PDO::PARAM_STR],
            [0, PDO::PARAM_INT],
            [1, PDO::PARAM_INT],
            [1337, PDO::PARAM_INT],
            [true, PDO::PARAM_BOOL],
            [false, PDO::PARAM_BOOL],
            [$fp = fopen(__FILE__, 'rb'), PDO::PARAM_LOB],
        ];

        $schema = $this->getConnection()->getSchema();

        foreach ($values as $value) {
            $this->assertEquals($value[1], $schema->getPdoType($value[0]));
        }

        fclose($fp);
    }

    public function testBooleanDefaultValues(): void
    {
        $schema = $this->getConnection()->getSchema();

        $table = $schema->getTableSchema('bool_values');

        $this->assertTrue($table->getColumn('default_true')->getDefaultValue());
        $this->assertFalse($table->getColumn('default_false')->getDefaultValue());
    }

    public function testGetSchemaNames(): void
    {
        $schema = $this->getConnection()->getSchema();

        $schemas = $schema->getSchemaNames();

        $this->assertNotEmpty($schemas);

        foreach ($this->expectedSchemas as $schema) {
            $this->assertContains($schema, $schemas);
        }
    }

    public function testSequenceName(): void
    {
        $connection = $this->getConnection();

        $sequenceName = $connection->getSchema()->getTableSchema('item')->getSequenceName();

        $connection->createCommand(
            'ALTER TABLE "item" ALTER COLUMN "id" SET DEFAULT nextval(\'item_id_seq_2\')'
        )->execute();

        $connection->getSchema()->refreshTableSchema('item');
        $this->assertEquals('item_id_seq_2', $connection->getSchema()->getTableSchema('item')->getSequenceName());

        $connection->createCommand(
            'ALTER TABLE "item" ALTER COLUMN "id" SET DEFAULT nextval(\'' . $sequenceName . '\')'
        )->execute();

        $connection->getSchema()->refreshTableSchema('item');

        $this->assertEquals($sequenceName, $connection->getSchema()->getTableSchema('item')->getSequenceName());
    }

    public function testGeneratedValues(): void
    {
        if (version_compare($this->getConnection()->getServerVersion(), '12.0', '<')) {
            $this->markTestSkipped('PostgreSQL < 12.0 does not support GENERATED AS IDENTITY columns.');
        }

        $this->prepareDatabase('@data/postgres12.sql');

        $table = $this->getConnection()->getSchema()->getTableSchema('generated');

        $this->assertTrue($table->getColumn('id_always')->isAutoIncrement());
        $this->assertTrue($table->getColumn('id_primary')->isAutoIncrement());
        $this->assertTrue($table->getColumn('id_primary')->isAutoIncrement());
        $this->assertTrue($table->getColumn('id_default')->isAutoIncrement());
    }

    public function testPartitionedTable(): void
    {
        if (version_compare($this->getConnection()->getServerVersion(), '10.0', '<')) {
            $this->markTestSkipped('PostgreSQL < 10.0 does not support PARTITION BY clause.');
        }

        $this->prepareDatabase('@data/postgres10.sql');

        $this->assertNotNull($this->getConnection()->getSchema()->getTableSchema('partitioned'));
    }

    public function testFindSchemaNames(): void
    {
        $schema = $this->getConnection()->getSchema();

        $this->assertCount(3, $schema->getSchemaNames());
    }

    public function bigintValueProvider(): array
    {
        return [
            [8817806877],
            [3797444208],
            [3199585540],
            [1389831585],
            [922337203685477580],
            [9223372036854775807],
            [-9223372036854775808],
        ];
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/12483
     */
    public function testParenthesisDefaultValue(): void
    {
        $db = $this->getConnection();

        if ($db->getSchema()->getTableSchema('test_default_parenthesis') !== null) {
            $db->createCommand()->dropTable('test_default_parenthesis')->execute();
        }

        $db->createCommand()->createTable('test_default_parenthesis', [
            'id' => 'pk',
            'user_timezone' => 'numeric(5,2) DEFAULT (0)::numeric NOT NULL',
        ])->execute();

        $db->getSchema()->refreshTableSchema('test_default_parenthesis');

        $tableSchema = $db->getSchema()->getTableSchema('test_default_parenthesis');

        $this->assertNotNull($tableSchema);

        $column = $tableSchema->getColumn('user_timezone');

        $this->assertNotNull($column);
        $this->assertFalse($column->isAllowNull());
        $this->assertEquals('numeric', $column->getDbType());
        $this->assertEquals(0, $column->getDefaultValue());
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/14192
     */
    public function testTimestampNullDefaultValue(): void
    {
        $db = $this->getConnection();

        if ($db->getSchema()->getTableSchema('test_timestamp_default_null') !== null) {
            $db->createCommand()->dropTable('test_timestamp_default_null')->execute();
        }

        $db->createCommand()->createTable('test_timestamp_default_null', [
            'id' => 'pk',
            'timestamp' => 'timestamp DEFAULT NULL',
        ])->execute();

        $db->getSchema()->refreshTableSchema('test_timestamp_default_null');

        $tableSchema = $db->getSchema()->getTableSchema('test_timestamp_default_null');

        $this->assertNull($tableSchema->getColumn('timestamp')->getDefaultValue());
    }

    /**
     * @dataProvider pdoAttributesProviderTrait
     *
     * @param array $pdoAttributes
     *
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testGetTableNames(array $pdoAttributes): void
    {
        $connection = $this->getConnection(true);

        foreach ($pdoAttributes as $name => $value) {
            $connection->getPDO()->setAttribute($name, $value);
        }

        $schema = $connection->getSchema();

        $tables = $schema->getTableNames();

        if ($connection->getDriverName() === 'sqlsrv') {
            $tables = array_map(static function ($item) {
                return trim($item, '[]');
            }, $tables);
        }

        $this->assertContains('customer', $tables);
        $this->assertContains('category', $tables);
        $this->assertContains('item', $tables);
        $this->assertContains('order', $tables);
        $this->assertContains('order_item', $tables);
        $this->assertContains('type', $tables);
        $this->assertContains('animal', $tables);
        $this->assertContains('animal_view', $tables);
    }

    /**
     * @dataProvider pdoAttributesProviderTrait
     *
     * @param array $pdoAttributes
     */
    public function testGetTableSchemas(array $pdoAttributes): void
    {
        $db = $this->getConnection(true);

        foreach ($pdoAttributes as $name => $value) {
            $db->getPDO()->setAttribute($name, $value);
        }

        $schema = $db->getSchema();

        $tables = $schema->getTableSchemas();

        $this->assertCount(count($schema->getTableNames()), $tables);

        foreach ($tables as $table) {
            $this->assertInstanceOf(TableSchema::class, $table);
        }
    }

    public function constraintsProvider(): array
    {
        $result = $this->constraintsProviderTrait();

        $result['1: check'][2][0]->expression('CHECK ((("C_check")::text <> \'\'::text))');
        $result['3: foreign key'][2][0]->foreignSchemaName('public');
        $result['3: index'][2] = [];

        return $result;
    }

    /**
     * @dataProvider constraintsProvider
     *
     * @param string $tableName
     * @param string $type
     * @param mixed $expected
     */
    public function testTableSchemaConstraints(string $tableName, string $type, $expected): void
    {
        if ($expected === false) {
            $this->expectException(NotSupportedException::class);
        }

        $constraints = $this->getConnection()->getSchema()->{'getTable' . ucfirst($type)}($tableName);

        $this->assertMetadataEquals($expected, $constraints);
    }

    /**
     * @dataProvider lowercaseConstraintsProviderTrait
     *
     * @param string $tableName
     * @param string $type
     * @param mixed $expected
     *
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testTableSchemaConstraintsWithPdoLowercase(string $tableName, string $type, $expected): void
    {
        if ($expected === false) {
            $this->expectException(NotSupportedException::class);
        }

        $connection = $this->getConnection();

        $connection->getSlavePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

        $constraints = $connection->getSchema()->{'getTable' . ucfirst($type)}($tableName, true);

        $this->assertMetadataEquals($expected, $constraints);
    }

    /**
     * @dataProvider uppercaseConstraintsProviderTrait
     *
     * @param string $tableName
     * @param string $type
     * @param mixed $expected
     *
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testTableSchemaConstraintsWithPdoUppercase(string $tableName, string $type, $expected): void
    {
        if ($expected === false) {
            $this->expectException(NotSupportedException::class);
        }

        $connection = $this->getConnection();

        $connection->getSlavePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);

        $constraints = $connection->getSchema()->{'getTable' . ucfirst($type)}($tableName, true);

        $this->assertMetadataEquals($expected, $constraints);
    }

    /**
     * @depends testSchemaCache
     *
     * @dataProvider tableSchemaCachePrefixesProviderTrait
     *
     * @param string $tablePrefix
     * @param string $tableName
     * @param string $testTablePrefix
     * @param string $testTableName
     */
    public function testTableSchemaCacheWithTablePrefixes(
        string $tablePrefix,
        string $tableName,
        string $testTablePrefix,
        string $testTableName
    ): void {
        $db = $this->getConnection();
        $schema = $this->getConnection()->getSchema();

        $db->getConnectionCache()->setEnableSchemaCache(true);
        $db->setTablePrefix($tablePrefix);

        $noCacheTable = $schema->getTableSchema($tableName, true);

        $this->assertInstanceOf(TableSchema::class, $noCacheTable);

        /* Compare */
        $db->setTablePrefix($testTablePrefix);

        $testNoCacheTable = $schema->getTableSchema($testTableName);

        $this->assertSame($noCacheTable, $testNoCacheTable);

        $db->setTablePrefix($tablePrefix);

        $schema->refreshTableSchema($tableName);

        $refreshedTable = $schema->getTableSchema($tableName, false);

        $this->assertInstanceOf(TableSchema::class, $refreshedTable);
        $this->assertNotSame($noCacheTable, $refreshedTable);

        /* Compare */
        $db->setTablePrefix($testTablePrefix);

        $schema->refreshTableSchema($testTablePrefix);

        $testRefreshedTable = $schema->getTableSchema($testTableName, false);

        $this->assertInstanceOf(TableSchema::class, $testRefreshedTable);
        $this->assertEquals($refreshedTable, $testRefreshedTable);
        $this->assertNotSame($testNoCacheTable, $testRefreshedTable);
    }
}
