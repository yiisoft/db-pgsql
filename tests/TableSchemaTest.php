<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Yiisoft\Db\Pgsql\QueryBuilder;

/**
 * @group pgsql
 */
final class TableSchemaTest extends TestCase
{
    public function testGetTableSchemaGetComment(): void
    {
        $db = $this->getConnection(true);
        $qb = new QueryBuilder($db);

        $sql = $qb->addCommentOnTable('comment', 'This is my table.');
        $db->createCommand($sql)->execute();
        $table = $db->getTableSchema('comment', true);

        $this->assertEquals('This is my table.', $table->getComment());
    }

    public function testGetTableSchemaGetCommentWithSchema(): void
    {
        $db = $this->getConnection(true);
        $qb = new QueryBuilder($db);

        $sql = $qb->addCommentOnTable('comment', 'This is my table.');
        $db->createCommand($sql)->execute();
        $table = $db->getTableSchema('public.comment', true);

        $this->assertEquals('This is my table.', $table->getComment());
    }
}
