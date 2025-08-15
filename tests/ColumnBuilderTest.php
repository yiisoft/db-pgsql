<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Pgsql\Tests\Provider\ColumnBuilderProvider;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\AbstractColumnBuilderTest;

/**
 * @group pgsql
 */
final class ColumnBuilderTest extends AbstractColumnBuilderTest
{
    use TestTrait;

    #[DataProviderExternal(ColumnBuilderProvider::class, 'buildingMethods')]
    public function testBuildingMethods(
        string $buildingMethod,
        array $args,
        string $expectedInstanceOf,
        string $expectedType,
        array $expectedMethodResults = [],
    ): void {
        parent::testBuildingMethods($buildingMethod, $args, $expectedInstanceOf, $expectedType, $expectedMethodResults);
    }
}
