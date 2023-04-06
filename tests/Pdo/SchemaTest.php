<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Pdo;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Pgsql\Schema;
use Yiisoft\Db\Tests\Support\DbHelper;

/**
 * @group pgsql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class SchemaTest extends \PHPUnit\Framework\TestCase
{
    public function testNotConnectionPDO(): void
    {
        $db = $this->createMock(ConnectionInterface::class);
        $schema = new Schema($db, DbHelper::getSchemaCache(), 'system');

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Only PDO connections are supported.');

        $schema->refreshTableSchema('customer');
    }
}
