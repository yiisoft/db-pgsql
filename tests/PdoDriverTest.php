<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PDO;
use Yiisoft\Db\Pgsql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Pgsql\Tests\Support\TestConnection;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;

/**
 * @group pgsql
 */
final class PdoDriverTest extends IntegrationTestCase
{
    use IntegrationTestTrait;

    public function testConnectionCharset(): void
    {
        $db = $this->createConnection();

        $pdo = $db->getActivePdo();
        $charset = $pdo->query('SHOW client_encoding', PDO::FETCH_ASSOC)->fetch();

        $this->assertEqualsIgnoringCase('UTF8', array_values($charset)[0]);

        $pdoDriver = TestConnection::createDriver();
        $pdoDriver->charset('latin1');
        $pdo = $pdoDriver->createConnection();
        $charset = $pdo->query('SHOW client_encoding', PDO::FETCH_ASSOC)->fetch();

        $this->assertEqualsIgnoringCase('latin1', array_values($charset)[0]);

        $db->close();
    }
}
