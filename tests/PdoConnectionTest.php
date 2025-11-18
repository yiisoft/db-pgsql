<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use InvalidArgumentException;
use Yiisoft\Db\Pgsql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Common\CommonPdoConnectionTest;

/**
 * @group pgsql
 */
final class PdoConnectionTest extends CommonPdoConnectionTest
{
    use IntegrationTestTrait;

    public function testGetLastInsertID(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $command = $db->createCommand();
        $command->insert(
            'customer',
            [
                'name' => 'Some {{weird}} name',
                'email' => 'test@example.com',
                'address' => 'Some {{%weird}} address',
            ],
        )->execute();

        $this->assertSame('4', $db->getLastInsertId('public.customer_id_seq'));
    }

    public function testGetLastInsertIDWithException(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $command = $db->createCommand();
        $command->insert('item', ['name' => 'Yii2 starter', 'category_id' => 1])->execute();
        $command->insert('item', ['name' => 'Yii3 starter', 'category_id' => 1])->execute();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('PostgreSQL not support lastInsertId without sequence name.');

        $db->getLastInsertId();
    }
}
