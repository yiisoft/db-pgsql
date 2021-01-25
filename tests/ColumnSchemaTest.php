<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Pgsql\ColumnSchema;

/**
 * @group pgsql
 */
final class ColumnSchemaTest extends TestCase
{
    public function testPhpTypeCast(): void
    {
        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('type');

        $command->insert(
            'type',
            [
                'int_col' => 1,
                'char_col' => str_repeat('x', 100),
                'char_col3' => null,
                'float_col' => 1.234,
                'blob_col' => "\x10\x11\x12",
                'bool_col' => false,
                'bigint_col' => 9223372036854775806,
                'intarray_col' => [1, -2, null, '42'],
                'textarray2_col' => new ArrayExpression(null),
                'json_col' => [['a' => 1, 'b' => null, 'c' => [1, 3, 5]]],
                'jsonb_col' => new JsonExpression(new ArrayExpression([1, 2, 3])),
                'jsonarray_col' => [new ArrayExpression([[',', 'null', true, 'false', 'f']], 'json')],
            ]
        );

        $command->execute();

        $query = (new Query($db))->from('type')->one();

        $intColPhpTypeCast = $tableSchema->getColumn('int_col')->phpTypecast($query['int_col']);
        $charColPhpTypeCast = $tableSchema->getColumn('char_col')->phpTypecast($query['char_col']);
        $floatColPhpTypeCast = $tableSchema->getColumn('float_col')->phpTypecast($query['float_col']);
        $boolColPhpTypeCast = $tableSchema->getColumn('bool_col')->phpTypecast($query['bool_col']);
        $numericColPhpTypeCast = $tableSchema->getColumn('numeric_col')->phpTypecast($query['numeric_col']);
        $intArrayColPhpType = $tableSchema->getColumn('intarray_col')->phpTypecast($query['intarray_col']);
        $textArray2ColPhpType = $tableSchema->getColumn('textarray2_col')->phpTypecast($query['textarray2_col']);
        $jsonColPhpType = $tableSchema->getColumn('json_col')->phpTypecast($query['json_col']);
        $jsonBColPhpType = $tableSchema->getColumn('jsonb_col')->phpTypecast($query['jsonb_col']);
        $jsonArrayColPhpType = $tableSchema->getColumn('jsonarray_col')->phpTypecast($query['jsonarray_col']);

        $this->assertSame(1, $intColPhpTypeCast);
        $this->assertSame(str_repeat('x', 100), $charColPhpTypeCast);
        $this->assertSame(1.234, $floatColPhpTypeCast);
        $this->assertSame(false, $boolColPhpTypeCast);
        $this->assertSame('33.22', $numericColPhpTypeCast);
        $this->assertSame([1, -2, null, 42], $intArrayColPhpType);
        $this->assertSame(null, $textArray2ColPhpType);
        $this->assertSame([['a' => 1, 'b' => null, 'c' => [1, 3, 5]]], $jsonColPhpType);
        $this->assertSame(['1', '2', '3'], $jsonBColPhpType);
        $this->assertSame([[[',', 'null', true, 'false', 'f']]], $jsonArrayColPhpType);
    }

    public function testPhpTypeCastBool(): void
    {
        $columnSchema = new ColumnSchema();
        $columnSchema->type('boolean');

        $this->assertFalse($columnSchema->phpTypeCast('false'));
        $this->assertTrue($columnSchema->phpTypeCast('true'));
    }
}
