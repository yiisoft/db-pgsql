<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Pgsql\Driver;
use Yiisoft\Db\Pgsql\Tests\Support\TestEnvironment;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;

/**
 * @group pgsql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class PDODriverTest extends TestCase
{
    use TestTrait;

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testConnectionCharset(): void
    {
        $db = $this->getConnection();

        $pdo = $db->getActivePDO();
        $charset = $pdo->query('SHOW client_encoding', PDO::FETCH_ASSOC)->fetch();

        $this->assertEqualsIgnoringCase('UTF8', array_values($charset)[0]);

        $dbHost = TestEnvironment::getPostgreSqlHost();
        $dbPort = TestEnvironment::getPostgreSqlPort();

        $pdoDriver = new Driver('pgsql:host=' . $dbHost . ';dbname=yiitest;port=' . $dbPort, 'root', 'root');
        $pdoDriver->charset('latin1');
        $pdo = $pdoDriver->createConnection();
        $charset = $pdo->query('SHOW client_encoding', PDO::FETCH_ASSOC)->fetch();

        $this->assertEqualsIgnoringCase('latin1', array_values($charset)[0]);

        $db->close();
    }
}
