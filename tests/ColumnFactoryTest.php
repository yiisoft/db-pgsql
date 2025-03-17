<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Pgsql\Column\ArrayColumn;
use Yiisoft\Db\Pgsql\Tests\Provider\ColumnFactoryProvider;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Tests\AbstractColumnFactoryTest;

/**
 * @group pgsql
 */
final class ColumnFactoryTest extends AbstractColumnFactoryTest
{
    use TestTrait;

    #[DataProviderExternal(ColumnFactoryProvider::class, 'dbTypes')]
    public function testFromDbType(string $dbType, string $expectedType, string $expectedInstanceOf): void
    {
        parent::testFromDbType($dbType, $expectedType, $expectedInstanceOf);

        $db = $this->getConnection();
        $columnFactory = $db->getColumnFactory();

        // For array type
        $column = $columnFactory->fromType(ColumnType::ARRAY, ['dbType' => $dbType]);

        $this->assertInstanceOf(ArrayColumn::class, $column);
        $this->assertInstanceOf($expectedInstanceOf, $column->getColumn());
        $this->assertSame($expectedType, $column->getColumn()->getType());
        $this->assertSame($dbType, $column->getColumn()->getDbType());

        $db->close();
    }

    #[DataProviderExternal(ColumnFactoryProvider::class, 'definitions')]
    public function testFromDefinition(string $definition, ColumnInterface $expected): void
    {
        parent::testFromDefinition($definition, $expected);
    }

    #[DataProviderExternal(ColumnFactoryProvider::class, 'pseudoTypes')]
    public function testFromPseudoType(string $pseudoType, ColumnInterface $expected): void
    {
        parent::testFromPseudoType($pseudoType, $expected);
    }

    #[DataProviderExternal(ColumnFactoryProvider::class, 'types')]
    public function testFromType(string $type, string $expectedType, string $expectedInstanceOf): void
    {
        parent::testFromType($type, $expectedType, $expectedInstanceOf);

        $db = $this->getConnection();
        $columnFactory = $db->getColumnFactory();

        // For array type
        $column = $columnFactory->fromType(ColumnType::ARRAY, ['column' => $columnFactory->fromType($type)]);

        $this->assertInstanceOf(ArrayColumn::class, $column);
        $this->assertInstanceOf($expectedInstanceOf, $column->getColumn());
        $this->assertSame($expectedType, $column->getColumn()->getType());

        $db->close();
    }

    #[DataProviderExternal(ColumnFactoryProvider::class, 'defaultValueRaw')]
    public function testFromTypeDefaultValueRaw(string $type, string|null $defaultValueRaw, mixed $expected): void
    {
        parent::testFromTypeDefaultValueRaw($type, $defaultValueRaw, $expected);
    }
}
