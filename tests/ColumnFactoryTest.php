<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Pgsql\Column\ArrayColumnSchema;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\AbstractColumnFactoryTest;

/**
 * @group pgsql
 */
final class ColumnFactoryTest extends AbstractColumnFactoryTest
{
    use TestTrait;

    /** @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\ColumnFactoryProvider::dbTypes */
    public function testFromDbType(string $dbType, string $expectedType, string $expectedInstanceOf): void
    {
        parent::testFromDbType($dbType, $expectedType, $expectedInstanceOf);

        $db = $this->getConnection();
        $columnFactory = $db->getSchema()->getColumnFactory();

        // For array type
        $column = $columnFactory->fromType(ColumnType::ARRAY, ['dbType' => $dbType]);

        $this->assertInstanceOf(ArrayColumnSchema::class, $column);
        $this->assertInstanceOf($expectedInstanceOf, $column->getColumn());
        $this->assertSame($expectedType, $column->getColumn()->getType());
        $this->assertSame($dbType, $column->getColumn()->getDbType());

        $db->close();
    }

    /** @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\ColumnFactoryProvider::definitions */
    public function testFromDefinition(
        string $definition,
        string $expectedType,
        string $expectedInstanceOf,
        array $expectedMethodResults = []
    ): void {
        parent::testFromDefinition($definition, $expectedType, $expectedInstanceOf, $expectedMethodResults);
    }

    /** @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\ColumnFactoryProvider::pseudoTypes */
    public function testFromPseudoType(
        string $pseudoType,
        string $expectedType,
        string $expectedInstanceOf,
        array $expectedMethodResults = []
    ): void {
        parent::testFromPseudoType($pseudoType, $expectedType, $expectedInstanceOf, $expectedMethodResults);
    }

    /** @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\ColumnFactoryProvider::types */
    public function testFromType(string $type, string $expectedType, string $expectedInstanceOf): void
    {
        parent::testFromType($type, $expectedType, $expectedInstanceOf);

        $db = $this->getConnection();
        $columnFactory = $db->getSchema()->getColumnFactory();

        // For array type
        $column = $columnFactory->fromType(ColumnType::ARRAY, ['column' => $columnFactory->fromType($type)]);

        $this->assertInstanceOf(ArrayColumnSchema::class, $column);
        $this->assertInstanceOf($expectedInstanceOf, $column->getColumn());
        $this->assertSame($expectedType, $column->getColumn()->getType());

        $db->close();
    }
}
