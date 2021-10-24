<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Yiisoft\Db\Pgsql\QueryBuilder;

/**
 * @group pgsql
 * @group schema
 */
final class TableSchemaTest extends TestCase
{
    /**
     * @dataProvider dataProviderSchemaWithComment
     */
    public function testGetTableSchemaGetComment(string $table, string $comment): void
    {
        $db = $this->getConnection(true);
        $qb = new QueryBuilder($db);

        $sql = $qb->addCommentOnTable($table, $comment);
        $db->createCommand($sql)->execute();
        $table = $db->getTableSchema($table, true);

        $this->assertEquals($comment, $table->getComment());
    }

    public function dataProviderSchemaWithComment()
    {
        return [
            'comment' => ['comment', 'This is my table.'],
            'comment with table schema' => ['public.comment', 'This is my table2.'],
            'comment and table name with quote' => ['comment\'complex', 'This is my table3.'],
            'comment and table name with quote and table schema' => ['public.comment\'complex', 'This is my table4.'],
            'table name with dot' => ['"comment.complex"', 'This is my table5.'],
            'table name with dot and table schema' => ['public."comment.complex"', 'This is my table6.'],
            'comment with quote1' => ['comment', 'This is \'my" table7.'],
            'comment with quote2' => ['comment', "This is 'my \" table8."],
        ];
    }
}
