<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Tests\SchemaTest as AbstractSchemaTest;

class SchemaTest extends AbstractSchemaTest
{
    protected ?string $driverName = 'pgsql';

    protected array $expectedSchemas = [
        'public',
    ];

    public function getExpectedColumns()
    {
        $version = $this->getConnection()->getPDO()->getAttribute(\PDO::ATTR_SERVER_VERSION);
        $columns = parent::getExpectedColumns();

        unset($columns['enum_col']);

        $columns['int_col']['dbType'] = 'int4';
        $columns['int_col']['size'] = null;
        $columns['int_col']['precision'] = 32;
        $columns['int_col']['scale'] = 0;
        $columns['int_col2']['dbType'] = 'int4';
        $columns['int_col2']['size'] = null;
        $columns['int_col2']['precision'] = 32;
        $columns['int_col2']['scale'] = 0;
        $columns['tinyint_col']['type'] = 'smallint';
        $columns['tinyint_col']['dbType'] = 'int2';
        $columns['tinyint_col']['size'] = null;
        $columns['tinyint_col']['precision'] = 16;
        $columns['tinyint_col']['scale'] = 0;
        $columns['smallint_col']['dbType'] = 'int2';
        $columns['smallint_col']['size'] = null;
        $columns['smallint_col']['precision'] = 16;
        $columns['smallint_col']['scale'] = 0;
        $columns['char_col']['dbType'] = 'bpchar';
        $columns['char_col']['precision'] = null;
        $columns['char_col2']['dbType'] = 'varchar';
        $columns['char_col2']['precision'] = null;
        $columns['float_col']['dbType'] = 'float8';
        $columns['float_col']['precision'] = 53;
        $columns['float_col']['scale'] = null;
        $columns['float_col']['size'] = null;
        $columns['float_col2']['dbType'] = 'float8';
        $columns['float_col2']['precision'] = 53;
        $columns['float_col2']['scale'] = null;
        $columns['float_col2']['size'] = null;
        $columns['blob_col']['dbType'] = 'bytea';
        $columns['blob_col']['phpType'] = 'resource';
        $columns['blob_col']['type'] = 'binary';
        $columns['numeric_col']['dbType'] = 'numeric';
        $columns['numeric_col']['size'] = null;
        $columns['bool_col']['type'] = 'boolean';
        $columns['bool_col']['phpType'] = 'boolean';
        $columns['bool_col']['dbType'] = 'bool';
        $columns['bool_col']['size'] = null;
        $columns['bool_col']['precision'] = null;
        $columns['bool_col']['scale'] = null;
        $columns['bool_col2']['type'] = 'boolean';
        $columns['bool_col2']['phpType'] = 'boolean';
        $columns['bool_col2']['dbType'] = 'bool';
        $columns['bool_col2']['size'] = null;
        $columns['bool_col2']['precision'] = null;
        $columns['bool_col2']['scale'] = null;
        $columns['bool_col2']['defaultValue'] = true;
        if (version_compare($version, '10', '<')) {
            $columns['ts_default']['defaultValue'] = new Expression('now()');
        }
        $columns['bit_col']['dbType'] = 'bit';
        $columns['bit_col']['size'] = 8;
        $columns['bit_col']['precision'] = null;
        $columns['bigint_col'] = [
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
        ];
        $columns['intarray_col'] = [
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
        ];
        $columns['textarray2_col'] = [
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
        ];
        $columns['json_col'] = [
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
        ];
        $columns['jsonb_col'] = [
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
        ];
        $columns['jsonarray_col'] = [
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
        ];

        return $columns;
    }

    public function testCompositeFk()
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

    public function testGetPDOType()
    {
        $values = [
            [null, \PDO::PARAM_NULL],
            ['', \PDO::PARAM_STR],
            ['hello', \PDO::PARAM_STR],
            [0, \PDO::PARAM_INT],
            [1, \PDO::PARAM_INT],
            [1337, \PDO::PARAM_INT],
            [true, \PDO::PARAM_BOOL],
            [false, \PDO::PARAM_BOOL],
            [$fp = fopen(__FILE__, 'rb'), \PDO::PARAM_LOB],
        ];

        $schema = $this->getConnection()->getSchema();

        foreach ($values as $value) {
            $this->assertEquals($value[1], $schema->getPdoType($value[0]));
        }
        fclose($fp);
    }

    public function testBooleanDefaultValues()
    {
        $schema = $this->getConnection()->getSchema();

        $table = $schema->getTableSchema('bool_values');
        $this->assertTrue($table->getColumn('default_true')->getDefaultValue());
        $this->assertFalse($table->getColumn('default_false')->getDefaultValue());
    }

    public function testSequenceName()
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

    public function testGeneratedValues()
    {
        if (version_compare($this->getConnection(false)->getServerVersion(), '12.0', '<')) {
            $this->markTestSkipped('PostgreSQL < 12.0 does not support GENERATED AS IDENTITY columns.');
        }

        $this->databases['fixture'] = '@data/postgres12.sql';

        $this->prepareDatabase(true, true, $this->databases);

        $table = $this->getConnection(false)->getSchema()->getTableSchema('generated');
        $this->assertTrue($table->getColumn('id_always')->isAutoIncrement());
        $this->assertTrue($table->getColumn('id_primary')->isAutoIncrement());
        $this->assertTrue($table->getColumn('id_primary')->isAutoIncrement());
        $this->assertTrue($table->getColumn('id_default')->isAutoIncrement());
    }

    public function testPartitionedTable()
    {
        if (version_compare($this->getConnection(false)->getServerVersion(), '10.0', '<')) {
            $this->markTestSkipped('PostgreSQL < 10.0 does not support PARTITION BY clause.');
        }

        $this->databases['fixture'] = '@data/postgres10.sql';

        $this->prepareDatabase(true, true, $this->databases);

        $this->assertNotNull($this->getConnection(false)->getSchema()->getTableSchema('partitioned'));
    }

    public function testFindSchemaNames()
    {
        $schema = $this->getConnection()->getSchema();

        $this->assertCount(3, $schema->getSchemaNames());
    }

    public function bigintValueProvider()
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
    public function testParenthesisDefaultValue()
    {
        $db = $this->getConnection(false);
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
    public function testTimestampNullDefaultValue()
    {
        $db = $this->getConnection(false);
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

    public function constraintsProvider(): array
    {
        $result = parent::constraintsProvider();

        $result['1: check'][2][0]->expression('CHECK ((("C_check")::text <> \'\'::text))');
        //$result['3: foreign key'][2][0]->setForeignSchemaName('public');
        $result['3: index'][2] = [];

        return $result;
    }
}
