<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PDO;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Pgsql\Column\ColumnBuilder;
use Yiisoft\Db\Pgsql\Column\ColumnFactory;
use Yiisoft\Db\Pgsql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Common\CommonConnectionTest;
use Yiisoft\Db\Transaction\TransactionInterface;

/**
 * @group pgsql
 */
final class ConnectionTest extends CommonConnectionTest
{
    use IntegrationTestTrait;

    public function testInitConnection(): void
    {
        $db = $this->createConnection();

        $db->setEmulatePrepare(true);
        $db->open();

        $this->assertTrue($db->getEmulatePrepare());

        $db->close();
    }

    public function testSettingDefaultAttributes(): void
    {
        $db = $this->createConnection();

        $this->assertSame(PDO::ERRMODE_EXCEPTION, $db->getActivePDO()->getAttribute(PDO::ATTR_ERRMODE));

        $db->close();
        $db->setEmulatePrepare(true);
        $db->open();

        $this->assertEquals(true, $db->getActivePDO()->getAttribute(PDO::ATTR_EMULATE_PREPARES));

        $db->close();
        $db->setEmulatePrepare(false);
        $db->open();

        $this->assertEquals(false, $db->getActivePDO()->getAttribute(PDO::ATTR_EMULATE_PREPARES));

        $db->close();
    }

    public function testTransactionIsolation(): void
    {
        $db = $this->createConnection();

        $transaction = $db->beginTransaction();
        $transaction->setIsolationLevel(TransactionInterface::READ_UNCOMMITTED);
        $transaction->commit();

        $transaction = $db->beginTransaction();
        $transaction->setIsolationLevel(TransactionInterface::READ_COMMITTED);
        $transaction->commit();

        $transaction = $db->beginTransaction();
        $transaction->setIsolationLevel(TransactionInterface::REPEATABLE_READ);
        $transaction->commit();

        $transaction = $db->beginTransaction();
        $transaction->setIsolationLevel(TransactionInterface::SERIALIZABLE);
        $transaction->commit();

        $transaction = $db->beginTransaction();
        $transaction->setIsolationLevel(TransactionInterface::SERIALIZABLE . ' READ ONLY DEFERRABLE');
        $transaction->commit();

        /* should not be any exception so far */
        $this->assertTrue(true);

        $db->close();
    }

    public function testTransactionShortcutCustom(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $this->assertTrue(
            $db->transaction(
                static function (ConnectionInterface $db) {
                    $db->createCommand()->insert('profile', ['description' => 'test transaction shortcut'])->execute();

                    return true;
                },
                TransactionInterface::READ_UNCOMMITTED,
            ),
            'transaction shortcut valid value should be returned from callback',
        );

        $this->assertEquals(
            1,
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM profile WHERE description = 'test transaction shortcut'
                SQL,
            )->queryScalar(),
            'profile should be inserted in transaction shortcut',
        );
    }

    public function getColumnBuilderClass(): void
    {
        $db = $this->getSharedConnection();

        $this->assertSame(ColumnBuilder::class, $db->getColumnBuilderClass());

        $db->close();
    }

    public function testGetColumnFactory(): void
    {
        $db = $this->getSharedConnection();

        $this->assertInstanceOf(ColumnFactory::class, $db->getColumnFactory());

        $db->close();
    }
}
