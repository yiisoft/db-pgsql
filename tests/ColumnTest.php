<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Throwable;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Pgsql\Column\ArrayColumn;
use Yiisoft\Db\Pgsql\Column\BigIntColumn;
use Yiisoft\Db\Pgsql\Column\BinaryColumn;
use Yiisoft\Db\Pgsql\Column\BitColumn;
use Yiisoft\Db\Pgsql\Column\BooleanColumn;
use Yiisoft\Db\Pgsql\Column\ColumnBuilder;
use Yiisoft\Db\Pgsql\Column\IntegerColumn;
use Yiisoft\Db\Pgsql\Column\StructuredColumn;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\JsonColumn;
use Yiisoft\Db\Schema\Column\StringColumn;
use Yiisoft\Db\Tests\AbstractColumnTest;

use function stream_get_contents;

/**
 * @group pgsql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ColumnTest extends AbstractColumnTest
{
    use TestTrait;

    private function insertTypeValues(Connection $db): void
    {
        $db->createCommand()->insert(
            'type',
            [
                'int_col' => 1,
                'char_col' => str_repeat('x', 100),
                'char_col3' => null,
                'float_col' => 1.234,
                'blob_col' => "\x10\x11\x12",
                'bool_col' => false,
                'bit_col' => 0b0110_0100, // 100
                'varbit_col' => 0b1_1100_1000, // 456
                'bigint_col' => 9_223_372_036_854_775_806,
                'intarray_col' => [1, -2, null, '42'],
                'numericarray_col' => [null, 1.2, -2.2, null, null],
                'varchararray_col' => ['', 'some text', '""', '\\\\', '[",","null",true,"false","f"]', null],
                'textarray2_col' => new ArrayExpression(null),
                'json_col' => [['a' => 1, 'b' => null, 'c' => [1, 3, 5]]],
                'jsonb_col' => new JsonExpression(new ArrayExpression([1, 2, 3])),
                'jsonarray_col' => [new ArrayExpression([[',', 'null', true, 'false', 'f']], ColumnType::JSON)],
            ]
        )->execute();
    }

    private function assertResultValues(array $result): void
    {
        $this->assertSame(1, $result['int_col']);
        $this->assertSame(str_repeat('x', 100), $result['char_col']);
        $this->assertSame(1.234, $result['float_col']);
        $this->assertSame("\x10\x11\x12", stream_get_contents($result['blob_col']));
        $this->assertFalse($result['bool_col']);
        $this->assertSame(0b0110_0100, $result['bit_col']);
        $this->assertSame(0b1_1100_1000, $result['varbit_col']);
        $this->assertSame(33.22, $result['numeric_col']);
        $this->assertSame([1, -2, null, 42], $result['intarray_col']);
        $this->assertSame([null, 1.2, -2.2, null, null], $result['numericarray_col']);
        $this->assertSame(['', 'some text', '""', '\\\\', '[",","null",true,"false","f"]', null], $result['varchararray_col']);
        $this->assertNull($result['textarray2_col']);
        $this->assertSame([['a' => 1, 'b' => null, 'c' => [1, 3, 5]]], $result['json_col']);
        $this->assertSame(['1', '2', '3'], $result['jsonb_col']);
        $this->assertSame([[[',', 'null', true, 'false', 'f']]], $result['jsonarray_col']);
    }

    public function testQueryTypecasting(): void
    {
        $db = $this->getConnection(true);

        $this->insertTypeValues($db);

        $result = (new Query($db))->typecasting()->from('type')->one();

        $this->assertResultValues($result);

        $db->close();
    }

    public function testCommandPhpTypecasting(): void
    {
        $db = $this->getConnection(true);

        $this->insertTypeValues($db);

        $result = $db->createCommand('SELECT * FROM type')->phpTypecasting()->queryOne();

        $this->assertResultValues($result);

        $db->close();
    }

    public function testSelectPhpTypecasting(): void
    {
        $db = $this->getConnection(true);

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
            '2.5' => 2.5,
            'true' => true,
            'false' => false,
            'string' => 'string',
            'enum' => 'VAL1',
            'enum2' => 'VAL2',
            'intarray' => [1, 2, 3],
            'jsonb' => ['a' => 1],
            'composite' => ['value' => 10.0, 'currency_code' => 'USD'],
        ];

        $result = $db->createCommand($sql)->phpTypecasting()->queryOne();

        $this->assertSame($expected, $result);

        $result = $db->createCommand($sql)->phpTypecasting()->queryAll();

        $this->assertSame([$expected], $result);

        $result = $db->createCommand('SELECT 2.5')->phpTypecasting()->queryScalar();

        $this->assertSame(2.5, $result);

        $result = $db->createCommand('SELECT 2.5 UNION SELECT 3.3')->phpTypecasting()->queryColumn();

        $this->assertSame([2.5, 3.3], $result);

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testPhpTypeCast(): void
    {
        $db = $this->getConnection(true);
        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('type');

        $this->insertTypeValues($db);

        $query = (new Query($db))->from('type')->one();

        $intColPhpTypeCast = $tableSchema->getColumn('int_col')?->phpTypecast($query['int_col']);
        $charColPhpTypeCast = $tableSchema->getColumn('char_col')?->phpTypecast($query['char_col']);
        $floatColPhpTypeCast = $tableSchema->getColumn('float_col')?->phpTypecast($query['float_col']);
        $blobColPhpTypeCast = $tableSchema->getColumn('blob_col')?->phpTypecast($query['blob_col']);
        $boolColPhpTypeCast = $tableSchema->getColumn('bool_col')?->phpTypecast($query['bool_col']);
        $bitColPhpTypeCast = $tableSchema->getColumn('bit_col')?->phpTypecast($query['bit_col']);
        $varbitColPhpTypeCast = $tableSchema->getColumn('varbit_col')?->phpTypecast($query['varbit_col']);
        $numericColPhpTypeCast = $tableSchema->getColumn('numeric_col')?->phpTypecast($query['numeric_col']);
        $intArrayColPhpType = $tableSchema->getColumn('intarray_col')?->phpTypecast($query['intarray_col']);
        $numericArrayColPhpTypeCast = $tableSchema->getColumn('numericarray_col')?->phpTypecast($query['numericarray_col']);
        $varcharArrayColPhpTypeCast = $tableSchema->getColumn('varchararray_col')?->phpTypecast($query['varchararray_col']);
        $textArray2ColPhpType = $tableSchema->getColumn('textarray2_col')?->phpTypecast($query['textarray2_col']);
        $jsonColPhpType = $tableSchema->getColumn('json_col')?->phpTypecast($query['json_col']);
        $jsonBColPhpType = $tableSchema->getColumn('jsonb_col')?->phpTypecast($query['jsonb_col']);
        $jsonArrayColPhpType = $tableSchema->getColumn('jsonarray_col')?->phpTypecast($query['jsonarray_col']);

        $this->assertSame(1, $intColPhpTypeCast);
        $this->assertSame(str_repeat('x', 100), $charColPhpTypeCast);
        $this->assertSame(1.234, $floatColPhpTypeCast);
        $this->assertSame("\x10\x11\x12", stream_get_contents($blobColPhpTypeCast));
        $this->assertFalse($boolColPhpTypeCast);
        $this->assertSame(0b0110_0100, $bitColPhpTypeCast);
        $this->assertSame(0b1_1100_1000, $varbitColPhpTypeCast);
        $this->assertSame(33.22, $numericColPhpTypeCast);
        $this->assertSame([1, -2, null, 42], $intArrayColPhpType);
        $this->assertSame([null, 1.2, -2.2, null, null], $numericArrayColPhpTypeCast);
        $this->assertSame(['', 'some text', '""', '\\\\', '[",","null",true,"false","f"]', null], $varcharArrayColPhpTypeCast);
        $this->assertNull($textArray2ColPhpType);
        $this->assertSame([['a' => 1, 'b' => null, 'c' => [1, 3, 5]]], $jsonColPhpType);
        $this->assertSame(['1', '2', '3'], $jsonBColPhpType);
        $this->assertSame([[[',', 'null', true, 'false', 'f']]], $jsonArrayColPhpType);

        $db->close();
    }

    public function testDbTypeCastJson(): void
    {
        $db = $this->getConnection(true);
        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('type');

        $this->assertEquals(new JsonExpression('', 'json'), $tableSchema->getColumn('json_col')->dbTypecast(''));
        $this->assertEquals(new JsonExpression('', 'jsonb'), $tableSchema->getColumn('jsonb_col')->dbTypecast(''));

        $db->close();
    }

    public function testBoolDefault(): void
    {
        $db = $this->getConnection(true);

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
            $tableSchema->getColumn('default_array')->phpTypecast($query['default_array'])
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
            $tableSchema->getColumn('default_array')->getDefaultValue()
        );

        $db->close();
    }

    public function testNegativeDefaultValues()
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('negative_default_values');

        $this->assertSame(-123, $tableSchema->getColumn('tinyint_col')->getDefaultValue());
        $this->assertSame(-123, $tableSchema->getColumn('smallint_col')->getDefaultValue());
        $this->assertSame(-123, $tableSchema->getColumn('int_col')->getDefaultValue());
        $this->assertSame(-123, $tableSchema->getColumn('bigint_col')->getDefaultValue());
        $this->assertSame(-12345.6789, $tableSchema->getColumn('float_col')->getDefaultValue());
        $this->assertSame(-33.22, $tableSchema->getColumn('numeric_col')->getDefaultValue());
    }

    public function testPrimaryKeyOfView()
    {
        $db = $this->getConnection(true);
        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('T_constraints_2_view');

        $this->assertSame(['C_id_1', 'C_id_2'], $tableSchema->getPrimaryKey());
        $this->assertTrue($tableSchema->getColumn('C_id_1')->isPrimaryKey());
        $this->assertTrue($tableSchema->getColumn('C_id_2')->isPrimaryKey());
        $this->assertFalse($tableSchema->getColumn('C_index_1')->isPrimaryKey());
        $this->assertFalse($tableSchema->getColumn('C_index_2_1')->isPrimaryKey());
        $this->assertFalse($tableSchema->getColumn('C_index_2_2')->isPrimaryKey());

        $db->close();
    }

    public function testStructuredType(): void
    {
        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('test_structured_type');

        $command->insert('test_structured_type', [
            'price_col' => ['value' => 10.0, 'currency_code' => 'USD'],
            'price_array' => [
                null,
                ['value' => 11.11, 'currency_code' => 'USD'],
                ['value' => null, 'currency_code' => null],
            ],
            'price_array2' => [[
                ['value' => 123.45, 'currency_code' => 'USD'],
            ]],
            'range_price_col' => [
                'price_from' => ['value' => 1000.0, 'currency_code' => 'USD'],
                'price_to' => ['value' => 2000.0, 'currency_code' => 'USD'],
            ],
        ])->execute();

        $query = (new Query($db))->from('test_structured_type')->one();

        $priceColPhpType = $tableSchema->getColumn('price_col')->phpTypecast($query['price_col']);
        $priceDefaultPhpType = $tableSchema->getColumn('price_default')->phpTypecast($query['price_default']);
        $priceArrayPhpType = $tableSchema->getColumn('price_array')->phpTypecast($query['price_array']);
        $priceArray2PhpType = $tableSchema->getColumn('price_array2')->phpTypecast($query['price_array2']);
        $rangePriceColPhpType = $tableSchema->getColumn('range_price_col')->phpTypecast($query['range_price_col']);

        $this->assertSame(['value' => 10.0, 'currency_code' => 'USD'], $priceColPhpType);
        $this->assertSame(['value' => 5.0, 'currency_code' => 'USD'], $priceDefaultPhpType);
        $this->assertSame(
            [
                null,
                ['value' => 11.11, 'currency_code' => 'USD'],
                ['value' => null, 'currency_code' => null],
            ],
            $priceArrayPhpType
        );
        $this->assertSame(
            [[
                ['value' => 123.45, 'currency_code' => 'USD'],
            ]],
            $priceArray2PhpType
        );
        $this->assertSame(
            [
                'price_from' => ['value' => 1000.0, 'currency_code' => 'USD'],
                'price_to' => ['value' => 2000.0, 'currency_code' => 'USD'],
            ],
            $rangePriceColPhpType
        );

        $priceCol = $tableSchema->getColumn('price_col');
        $priceCol->columns([]);
        $this->assertSame(['5', 'USD'], $priceCol->phpTypecast('(5,USD)'), 'No type casting for empty columns');

        $priceArray2 = $tableSchema->getColumn('price_array2');
        $this->assertEquals(
            new ArrayExpression(
                [null, null],
                new ArrayColumn(
                    dbType: 'currency_money_structured',
                    dimension: 2,
                    name: 'price_array2',
                    notNull: false,
                    column: new StructuredColumn(
                        dbType: 'currency_money_structured',
                        name: 'price_array2',
                        notNull: false,
                        columns: [
                            'value' => new DoubleColumn(ColumnType::DECIMAL, dbType: 'numeric', name: 'value', notNull: false, scale: 2, size: 10),
                            'currency_code' => new StringColumn(ColumnType::CHAR, dbType: 'bpchar', name: 'currency_code', notNull: false, size: 3),
                        ],
                    ),
                ),
            ),
            $priceArray2->dbTypecast([null, null]),
            'Double array of null values'
        );

        $db->close();
    }

    public function testColumnInstance()
    {
        $db = $this->getConnection(true);
        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('type');

        $this->assertInstanceOf(IntegerColumn::class, $tableSchema->getColumn('int_col'));
        $this->assertInstanceOf(StringColumn::class, $tableSchema->getColumn('char_col'));
        $this->assertInstanceOf(DoubleColumn::class, $tableSchema->getColumn('float_col'));
        $this->assertInstanceOf(BinaryColumn::class, $tableSchema->getColumn('blob_col'));
        $this->assertInstanceOf(BooleanColumn::class, $tableSchema->getColumn('bool_col'));
        $this->assertInstanceOf(BitColumn::class, $tableSchema->getColumn('bit_col'));
        $this->assertInstanceOf(ArrayColumn::class, $tableSchema->getColumn('intarray_col'));
        $this->assertInstanceOf(IntegerColumn::class, $tableSchema->getColumn('intarray_col')->getColumn());
        $this->assertInstanceOf(JsonColumn::class, $tableSchema->getColumn('json_col'));

        $db->close();
    }

    /** @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\ColumnProvider::predefinedTypes */
    public function testPredefinedType(string $className, string $type, string $phpType)
    {
        parent::testPredefinedType($className, $type, $phpType);
    }

    /** @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\ColumnProvider::dbTypecastColumns */
    public function testDbTypecastColumns(ColumnInterface $column, array $values)
    {
        parent::testDbTypecastColumns($column, $values);
    }

    /** @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\ColumnProvider::phpTypecastColumns */
    public function testPhpTypecastColumns(ColumnInterface $column, array $values)
    {
        parent::testPhpTypecastColumns($column, $values);
    }

    /** @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\ColumnProvider::phpTypecastArrayColumns */
    public function testPhpTypecastArrayColumn(ColumnInterface $column, array $values): void
    {
        $arrayCol = ColumnBuilder::array($column);

        foreach ($values as [$dimension, $expected, $value]) {
            $arrayCol->dimension($dimension);
            $this->assertSame($expected, $arrayCol->phpTypecast($value));
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
}
