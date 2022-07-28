<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PDO;
use Yiisoft\Db\Pgsql\PDODriver;

/**
 * @group mysql
 */
final class DriverTest extends TestCase
{
    public function testConnectionCharset(): void
    {
        $pdoDriver = new PDODriver($this->dsn, $this->username, $this->password);

        $pdo = $pdoDriver->createConnection();
        $charset = $pdo->query('SHOW client_encoding', PDO::FETCH_ASSOC)->fetch();
        $this->assertEqualsIgnoringCase($this->charset, array_values($charset)[0]);

        $newCharset = 'latin1';
        $pdoDriver->setCharset($newCharset);
        $pdo = $pdoDriver->createConnection();
        $charset = $pdo->query('SHOW client_encoding', PDO::FETCH_ASSOC)->fetch();
        $this->assertEqualsIgnoringCase($newCharset, array_values($charset)[0]);
    }
}
