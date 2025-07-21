<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Pgsql\Dsn;

/**
 * @group pgsql
 */
final class DsnTest extends TestCase
{
    public function testConstruct(): void
    {
        $dsn = new Dsn('pgsql', 'localhost', 'yiitest', '5433', ['sslmode' => 'disable']);

        $this->assertSame('pgsql', $dsn->driver);
        $this->assertSame('localhost', $dsn->host);
        $this->assertSame('yiitest', $dsn->databaseName);
        $this->assertSame('5433', $dsn->port);
        $this->assertSame(['sslmode' => 'disable'], $dsn->options);
        $this->assertSame('pgsql:host=localhost;dbname=yiitest;port=5433;sslmode=disable', (string) $dsn);
    }

    public function testConstructDefaults(): void
    {
        $dsn = new Dsn();

        $this->assertSame('pgsql', $dsn->driver);
        $this->assertSame('127.0.0.1', $dsn->host);
        $this->assertSame('postgres', $dsn->databaseName);
        $this->assertSame('5432', $dsn->port);
        $this->assertSame([], $dsn->options);
        $this->assertSame('pgsql:host=127.0.0.1;dbname=postgres;port=5432', (string) $dsn);
    }

    public function testConstructWithEmptyPort(): void
    {
        $dsn = new Dsn('pgsql', 'localhost', '', '');

        $this->assertSame('pgsql', $dsn->driver);
        $this->assertSame('localhost', $dsn->host);
        $this->assertSame('postgres', $dsn->databaseName);
        $this->assertSame('', $dsn->port);
        $this->assertSame([], $dsn->options);
        $this->assertSame('pgsql:host=localhost;dbname=postgres', (string) $dsn);
    }
}
