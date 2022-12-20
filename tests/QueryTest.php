<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\AbstractQueryTest;

/**
 * @group pgsql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class QueryTest extends AbstractQueryTest
{
    use TestTrait;

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testBooleanValues(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->batchInsert('bool_values', ['bool_col'], [[true], [false]])->execute();

        $this->assertSame(1, (new Query($db))->from('bool_values')->where('bool_col = TRUE')->count());
        $this->assertSame(1, (new Query($db))->from('bool_values')->where('bool_col = FALSE')->count());
        $this->assertSame(
            2,
            (new Query($db))->from('bool_values')->where('bool_col IN (TRUE, FALSE)')->count()
        );
        $this->assertSame(1, (new Query($db))->from('bool_values')->where(['bool_col' => true])->count());
        $this->assertSame(1, (new Query($db))->from('bool_values')->where(['bool_col' => false])->count());
        $this->assertSame(
            2,
            (new Query($db))->from('bool_values')->where(['bool_col' => [true, false]])->count()
        );
        $this->assertSame(
            1,
            (new Query($db))->from('bool_values')->where('bool_col = :bool_col', ['bool_col' => true])->count()
        );
        $this->assertSame(
            1,
            (new Query($db))->from('bool_values')->where('bool_col = :bool_col', ['bool_col' => false])->count()
        );

        $db->close();
    }
}
