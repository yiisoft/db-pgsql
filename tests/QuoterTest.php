<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Pgsql\Tests\Provider\QuoterProvider;
use Yiisoft\Db\Pgsql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Common\CommonQuoterTest;

/**
 * @group pgsql
 */
final class QuoterTest extends CommonQuoterTest
{
    use IntegrationTestTrait;

    #[DataProviderExternal(QuoterProvider::class, 'tableNameParts')]
    public function testGetTableNameParts(string $tableName, array $expected): void
    {
        parent::testGetTableNameParts($tableName, $expected);
    }
}
