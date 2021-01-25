<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use ArrayAccess;
use Traversable;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Query\Query;

/**
 * @group pgsql
 */
final class ColumnSchemaTest extends TestCase
{
    public function testDbTypes(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $command->insert(
            'type',
            [
                'int_col' => 1,
                'char_col' => str_repeat('x', 100),
                'char_col3' => str_repeat('x', 101),
                'float_col' => 1.234,
                'blob_col' => "\x10\x11\x12",
                'bool_col' => false,
                'bigint_col' => 9223372036854775806,
                'intarray_col' => [1, -2, null, '42'],
                'textarray2_col' => new ArrayExpression([['text'], [null], [1]], 'text', 2),
                'json_col' => [['a' => 1, 'b' => null, 'c' => [1, 3, 5]]],
                'jsonb_col' => new JsonExpression(new ArrayExpression([1, 2, 3])),
                'jsonarray_col' => [new ArrayExpression([[',', 'null', true, 'false', 'f']], 'json')],
            ]
        );

        $command->execute();

        $query = (new Query($db))->from('type')->one();

        $this->assertSame(1, $query['int_col']);
        $this->assertSame(1, $query['int_col2']);
        $this->assertSame(1, $query['tinyint_col']);
        $this->assertSame(1, $query['smallint_col']);
        $this->assertSame(str_repeat('x', 100), $query['char_col']);
        $this->assertSame('something', $query['char_col2']);
        $this->assertSame(str_repeat('x', 101), $query['char_col3']);
        $this->assertSame('1.234', $query['float_col']);
        $this->assertSame('1.23', $query['float_col2']);
        $this->assertSame("\x10\x11\x12", stream_get_contents($query['blob_col']));
        $this->assertSame('33.22', $query['numeric_col']);
        $this->assertSame('2002-01-01 00:00:00', $query['time']);
        $this->assertSame(false, $query['bool_col']);
        $this->assertSame(true, $query['bool_col2']);
        $this->assertSame('10000010', $query['bit_col']);
        $this->assertSame(9223372036854775806, $query['bigint_col']);
        $this->assertSame('{1,-2,NULL,42}', $query['intarray_col']);
        $this->assertSame('{{text},{NULL},{1}}', $query['textarray2_col']);
        $this->assertSame('[{"a":1,"b":null,"c":[1,3,5]}]', $query['json_col']);
        $this->assertSame('["1", "2", "3"]', $query['jsonb_col']);
        $this->assertSame('{{"[\",\",\"null\",true,\"false\",\"f\"]"}}', $query['jsonarray_col']);
    }
}
