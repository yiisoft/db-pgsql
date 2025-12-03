<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\Value\ArrayValue;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\Value\JsonValue;
use Yiisoft\Db\Pgsql\Column\ArrayColumn;
use Yiisoft\Db\Pgsql\Column\BigBitColumn;
use Yiisoft\Db\Pgsql\Column\BigIntColumn;
use Yiisoft\Db\Pgsql\Column\BinaryColumn;
use Yiisoft\Db\Pgsql\Column\BitColumn;
use Yiisoft\Db\Pgsql\Column\BooleanColumn;
use Yiisoft\Db\Pgsql\Column\ColumnBuilder;
use Yiisoft\Db\Pgsql\Column\IntegerColumn;
use Yiisoft\Db\Pgsql\Column\StructuredColumn;
use Yiisoft\Db\Pgsql\Tests\Provider\ColumnProvider;
use Yiisoft\Db\Pgsql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Pgsql\Tests\Support\TestConnection;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\JsonColumn;
use Yiisoft\Db\Schema\Column\StringColumn;
use Yiisoft\Db\Tests\Common\CommonColumnTest;
use Yiisoft\Db\Tests\Support\Assert;

use function iterator_to_array;
use function str_repeat;

/**
 * @group pgsql
 */
final class ColumnTest extends CommonColumnTest
{
    use IntegrationTestTrait;

    public function testSelectWithPhpTypecasting(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $sql = <<<SQL
            SELECT
                null AS "null",
                1 AS "1",
                2.5 AS "2.5",
                true AS "true",
                false AS "false",
                'string' AS "string",
                'VAL1'::my_type AS "enum",
                'VAL2'::schema2.my_type2 AS "enum2",
                '{1,2,3}'::int[] AS "intarray",
                '{"a":1}'::jsonb AS "jsonb",
                '(10,USD)'::currency_money_structured AS "composite"
            SQL;

        $expected = [
            'null' => null,
            1 => 1,
            '2.5' => '2.5',
            'true' => true,
            'false' => false,
            'string' => 'string',
            'enum' => 'VAL1',
            'enum2' => 'VAL2',
            'intarray' => [1, 2, 3],
            'jsonb' => ['a' => 1],
            'composite' => ['value' => '10.00', 'currency_code' => 'USD'],
        ];

        $result = $db->createCommand($sql)
            ->withPhpTypecasting()
            ->queryOne();

        $this->assertSame($expected, $result);

        $result = $db->createCommand($sql)
            ->withPhpTypecasting()
            ->queryAll();

        $this->assertSame([$expected], $result);

        $result = $db->createCommand($sql)
            ->withPhpTypecasting()
            ->query();

        $this->assertSame([$expected], iterator_to_array($result));

        $result = $db->createCommand('SELECT 2.5')
            ->withPhpTypecasting()
            ->queryScalar();

        $this->assertSame('2.5', $result);

        $result = $db->createCommand('SELECT 2.5 UNION SELECT 3.3')
            ->withPhpTypecasting()
            ->queryColumn();

        $this->assertSame(['2.5', '3.3'], $result);
    }

    public function testDbTypeCastJson(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('type');

        $this->assertEquals(new JsonValue('', 'json'), $tableSchema->getColumn('json_col')->dbTypecast(''));
        $this->assertEquals(new JsonValue('', 'jsonb'), $tableSchema->getColumn('jsonb_col')->dbTypecast(''));
    }

    public function testBoolDefault(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $command = $db->createCommand();
        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('bool_values');
        $command->insert('bool_values', ['id' => new Expression('DEFAULT')]);
        $command->execute();
        $query = (new Query($db))->from('bool_values')->one();

        $this->assertNull($query['bool_col']);
        $this->assertTrue($query['default_true']);
        $this->assertTrue($query['default_qtrueq']);
        $this->assertTrue($query['default_t']);
        $this->assertTrue($query['default_yes']);
        $this->assertTrue($query['default_on']);
        $this->assertTrue($query['default_1']);
        $this->assertFalse($query['default_false']);
        $this->assertFalse($query['default_qfalseq']);
        $this->assertFalse($query['default_f']);
        $this->assertFalse($query['default_no']);
        $this->assertFalse($query['default_off']);
        $this->assertFalse($query['default_0']);
        $this->assertSame(
            [null, true, true, true, true, true, true, false, false, false, false, false, false],
            $tableSchema->getColumn('default_array')->phpTypecast($query['default_array']),
        );

        $this->assertNull($tableSchema->getColumn('bool_col')->getDefaultValue());
        $this->assertTrue($tableSchema->getColumn('default_true')->getDefaultValue());
        $this->assertTrue($tableSchema->getColumn('default_qtrueq')->getDefaultValue());
        $this->assertTrue($tableSchema->getColumn('default_t')->getDefaultValue());
        $this->assertTrue($tableSchema->getColumn('default_yes')->getDefaultValue());
        $this->assertTrue($tableSchema->getColumn('default_on')->getDefaultValue());
        $this->assertTrue($tableSchema->getColumn('default_1')->getDefaultValue());
        $this->assertFalse($tableSchema->getColumn('default_false')->getDefaultValue());
        $this->assertFalse($tableSchema->getColumn('default_qfalseq')->getDefaultValue());
        $this->assertFalse($tableSchema->getColumn('default_f')->getDefaultValue());
        $this->assertFalse($tableSchema->getColumn('default_no')->getDefaultValue());
        $this->assertFalse($tableSchema->getColumn('default_off')->getDefaultValue());
        $this->assertFalse($tableSchema->getColumn('default_0')->getDefaultValue());
        $this->assertSame(
            [null, true, true, true, true, true, true, false, false, false, false, false, false],
            $tableSchema->getColumn('default_array')->getDefaultValue(),
        );
    }

    public function testPrimaryKeyOfView()
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('T_constraints_2_view');

        $this->assertSame(['C_id_1', 'C_id_2'], $tableSchema->getPrimaryKey());
        $this->assertTrue($tableSchema->getColumn('C_id_1')->isPrimaryKey());
        $this->assertTrue($tableSchema->getColumn('C_id_2')->isPrimaryKey());
        $this->assertFalse($tableSchema->getColumn('C_index_1')->isPrimaryKey());
        $this->assertFalse($tableSchema->getColumn('C_index_2_1')->isPrimaryKey());
        $this->assertFalse($tableSchema->getColumn('C_index_2_2')->isPrimaryKey());
    }

    public function testStructuredType(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $command = $db->createCommand();
        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('test_structured_type');

        $command
            ->insert(
                'test_structured_type',
                [
                    'price_col' => ['value' => 10.0, 'currency_code' => 'USD'],
                    'price_array' => [
                        null,
                        ['value' => 11.11, 'currency_code' => 'USD'],
                        ['value' => null, 'currency_code' => null],
                    ],
                    'price_array2' => [
                        [
                            ['value' => 123.45, 'currency_code' => 'USD'],
                        ]
                    ],
                    'range_price_col' => [
                        'price_from' => ['value' => 1000.0, 'currency_code' => 'USD'],
                        'price_to' => ['value' => 2000.0, 'currency_code' => 'USD'],
                    ],
                ]
            )
            ->execute();

        $query = (new Query($db))->from('test_structured_type')->one();

        $priceColPhpType = $tableSchema->getColumn('price_col')->phpTypecast($query['price_col']);
        $priceDefaultPhpType = $tableSchema->getColumn('price_default')->phpTypecast($query['price_default']);
        $priceArrayPhpType = $tableSchema->getColumn('price_array')->phpTypecast($query['price_array']);
        $priceArray2PhpType = $tableSchema->getColumn('price_array2')->phpTypecast($query['price_array2']);
        $rangePriceColPhpType = $tableSchema->getColumn('range_price_col')->phpTypecast($query['range_price_col']);

        $this->assertSame(['value' => '10.00', 'currency_code' => 'USD'], $priceColPhpType);
        $this->assertSame(['value' => '5.00', 'currency_code' => 'USD'], $priceDefaultPhpType);
        $this->assertSame(
            [
                null,
                ['value' => '11.11', 'currency_code' => 'USD'],
                ['value' => null, 'currency_code' => null],
            ],
            $priceArrayPhpType,
        );
        $this->assertSame(
            [[
                ['value' => '123.45', 'currency_code' => 'USD'],
            ]],
            $priceArray2PhpType,
        );
        $this->assertSame(
            [
                'price_from' => ['value' => '1000.00', 'currency_code' => 'USD'],
                'price_to' => ['value' => '2000.00', 'currency_code' => 'USD'],
            ],
            $rangePriceColPhpType,
        );

        $priceCol = $tableSchema->getColumn('price_col');
        $priceCol->columns([]);
        $this->assertSame(['5', 'USD'], $priceCol->phpTypecast('(5,USD)'), 'No type casting for empty columns');

        $priceArray2 = $tableSchema->getColumn('price_array2');
        $this->assertEquals(
            new ArrayValue(
                [null, null],
                new ArrayColumn(
                    dbType: 'currency_money_structured',
                    name: 'price_array2',
                    notNull: false,
                    dimension: 2,
                    column: new StructuredColumn(
                        dbType: 'currency_money_structured',
                        name: 'price_array2',
                        notNull: false,
                        columns: [
                            'value' => new StringColumn(
                                ColumnType::DECIMAL,
                                dbType: 'numeric',
                                name: 'value',
                                notNull: false,
                                scale: 2,
                                size: 10,
                                defaultValue: null,
                            ),
                            'currency_code' => new StringColumn(
                                ColumnType::CHAR,
                                dbType: 'bpchar',
                                name: 'currency_code',
                                notNull: false,
                                size: 3,
                                defaultValue: null,
                            ),
                        ],
                    ),
                    defaultValue: null,
                ),
            ),
            $priceArray2->dbTypecast([null, null]),
            'Double array of null values',
        );
    }

    public function testColumnInstance()
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('type');

        $this->assertInstanceOf(IntegerColumn::class, $tableSchema->getColumn('int_col'));
        $this->assertInstanceOf(StringColumn::class, $tableSchema->getColumn('char_col'));
        $this->assertInstanceOf(DoubleColumn::class, $tableSchema->getColumn('float_col'));
        $this->assertInstanceOf(BinaryColumn::class, $tableSchema->getColumn('blob_col'));
        $this->assertInstanceOf(BooleanColumn::class, $tableSchema->getColumn('bool_col'));
        $this->assertInstanceOf(BitColumn::class, $tableSchema->getColumn('bit_col'));
        $this->assertInstanceOf(BigBitColumn::class, $tableSchema->getColumn('bigbit_col'));
        $this->assertInstanceOf(ArrayColumn::class, $tableSchema->getColumn('intarray_col'));
        $this->assertInstanceOf(IntegerColumn::class, $tableSchema->getColumn('intarray_col')->getColumn());
        $this->assertInstanceOf(JsonColumn::class, $tableSchema->getColumn('json_col'));
    }

    #[DataProviderExternal(ColumnProvider::class, 'predefinedTypes')]
    public function testPredefinedType(string $className, string $type)
    {
        parent::testPredefinedType($className, $type);
    }

    #[DataProviderExternal(ColumnProvider::class, 'dbTypecastColumns')]
    public function testDbTypecastColumns(ColumnInterface $column, array $values)
    {
        parent::testDbTypecastColumns($column, $values);
    }

    #[DataProviderExternal(ColumnProvider::class, 'phpTypecastColumns')]
    public function testPhpTypecastColumns(ColumnInterface $column, array $values)
    {
        parent::testPhpTypecastColumns($column, $values);
    }

    #[DataProviderExternal(ColumnProvider::class, 'dbTypecastArrayColumns')]
    public function testArrayColumnDbTypecast(ColumnInterface $column, array $values): void
    {
        $arrayCol = (new ArrayColumn())->column($column);

        foreach ($values as [$dimension, $expected, $value]) {
            $arrayCol->dimension($dimension);
            $dbValue = $arrayCol->dbTypecast($value);

            $this->assertInstanceOf(ArrayValue::class, $dbValue);
            $this->assertSame($arrayCol, $dbValue->type);
            $this->assertEquals($value, $dbValue->value);
        }
    }

    #[DataProviderExternal(ColumnProvider::class, 'phpTypecastArrayColumns')]
    public function testPhpTypecastArrayColumn(ColumnInterface $column, array $values): void
    {
        $arrayCol = ColumnBuilder::array($column);

        foreach ($values as [$dimension, $expected, $value]) {
            $arrayCol->dimension($dimension);

            Assert::arraysEquals($expected, $arrayCol->phpTypecast($value));
        }
    }

    public function testIntegerColumn()
    {
        $intCol = new IntegerColumn();

        $this->assertNull($intCol->getSequenceName());
        $this->assertSame($intCol, $intCol->sequenceName('int_seq'));
        $this->assertSame('int_seq', $intCol->getSequenceName());

        $intCol->sequenceName(null);

        $this->assertNull($intCol->getSequenceName());
    }

    public function testBigIntColumn()
    {
        $bigintCol = new BigIntColumn();

        $this->assertNull($bigintCol->getSequenceName());
        $this->assertSame($bigintCol, $bigintCol->sequenceName('bigint_seq'));
        $this->assertSame('bigint_seq', $bigintCol->getSequenceName());

        $bigintCol->sequenceName(null);

        $this->assertNull($bigintCol->getSequenceName());
    }

    protected function insertTypeValues(ConnectionInterface $db): void
    {
        $db->createCommand()->insert(
            'type',
            [
                'int_col' => 1,
                'char_col' => str_repeat('x', 100),
                'char_col3' => null,
                'float_col' => 1.234,
                'blob_col' => "\x10\x11\x12",
                'timestamp_col' => '2023-07-11 14:50:23',
                'timestamp_default' => new DateTimeImmutable('2023-07-11 14:50:23'),
                'bool_col' => false,
                'bit_col' => 0b0110_0100, // 100
                'varbit_col' => 0b1_1100_1000, // 456
                'bigint_col' => 9_223_372_036_854_775_806,
                'intarray_col' => [1, -2, null, '42'],
                'numericarray_col' => [null, 1.2, -2.2, null, null],
                'varchararray_col' => ['', 'some text', '""', '\\\\', '[",","null",true,"false","f"]', null],
                'textarray2_col' => new ArrayValue(null),
                'json_col' => [['a' => 1, 'b' => null, 'c' => [1, 3, 5]]],
                'jsonb_col' => new JsonValue(new ArrayValue([1, 2, 3])),
                'jsonarray_col' => [new ArrayValue([[',', 'null', true, 'false', 'f']], ColumnType::JSON)],
            ],
        )->execute();
    }

    protected function assertTypecastedValues(array $result, bool $allTypecasted = false): void
    {
        $this->assertSame(1, $result['int_col']);
        $this->assertSame(str_repeat('x', 100), $result['char_col']);
        $this->assertSame(1.234, $result['float_col']);
        $this->assertSame("\x10\x11\x12", (string) $result['blob_col']);
        $this->assertEquals(new DateTimeImmutable('2023-07-11 14:50:23', new DateTimeZone('UTC')), $result['timestamp_col']);
        $this->assertEquals(new DateTimeImmutable('2023-07-11 14:50:23'), $result['timestamp_default']);
        $this->assertFalse($result['bool_col']);
        $this->assertSame(0b0110_0100, $result['bit_col']);
        $this->assertSame(0b1_1100_1000, $result['varbit_col']);
        $this->assertSame('33.22', $result['numeric_col']);
        $this->assertSame([1, -2, null, 42], $result['intarray_col']);
        $this->assertSame([null, '1.20', '-2.20', null, null], $result['numericarray_col']);
        $this->assertSame(['', 'some text', '""', '\\\\', '[",","null",true,"false","f"]', null], $result['varchararray_col']);
        $this->assertNull($result['textarray2_col']);
        $this->assertSame([['a' => 1, 'b' => null, 'c' => [1, 3, 5]]], $result['json_col']);
        $this->assertSame([1, 2, 3], $result['jsonb_col']);
        $this->assertSame([[[',', 'null', true, 'false', 'f']]], $result['jsonarray_col']);
    }

    protected function createTimestampDefaultValue(): mixed
    {
        return new Expression(
            version_compare(TestConnection::getServerVersion(), '10', '<')
                ? 'now()'
                : 'CURRENT_TIMESTAMP',
        );
    }
}
