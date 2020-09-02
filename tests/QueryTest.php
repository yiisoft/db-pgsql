<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Yiisoft\Db\Query\Query;
use Yiisoft\Db\TestUtility\TestQueryTrait;

/**
 * @group pgsql
 */
final class QueryTest extends TestCase
{
    use TestQueryTrait;

    public function testBooleanValues(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $command->batchInsert(
            'bool_values',
            ['bool_col'],
            [
                [true],
                [false],
            ]
        )->execute();

        $this->assertEquals(1, (new Query($db))->from('bool_values')->where('bool_col = TRUE')->count('*', $db));
        $this->assertEquals(1, (new Query($db))->from('bool_values')->where('bool_col = FALSE')->count('*', $db));
        $this->assertEquals(
            2,
            (new Query($db))->from('bool_values')->where('bool_col IN (TRUE, FALSE)')->count('*', $db)
        );
        $this->assertEquals(1, (new Query($db))->from('bool_values')->where(['bool_col' => true])->count('*', $db));
        $this->assertEquals(1, (new Query($db))->from('bool_values')->where(['bool_col' => false])->count('*', $db));
        $this->assertEquals(
            2,
            (new Query($db))->from('bool_values')->where(['bool_col' => [true, false]])->count('*', $db)
        );
        $this->assertEquals(
            1,
            (new Query($db))->from('bool_values')->where('bool_col = :bool_col', ['bool_col' => true])->count('*', $db)
        );
        $this->assertEquals(
            1,
            (new Query($db))->from('bool_values')->where('bool_col = :bool_col', ['bool_col' => false])->count('*', $db)
        );
    }
}
