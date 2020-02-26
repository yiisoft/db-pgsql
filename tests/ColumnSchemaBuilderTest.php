<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Yiisoft\Db\Schemas\ColumnSchemaBuilder;
use Yiisoft\Db\Tests\ColumnSchemaBuilderTest as AbstractColumnSchemaBuilderTest;

class ColumnSchemaBuilderTest extends AbstractColumnSchemaBuilderTest
{
    protected ?string $driverName = 'pgsql';

    /**
     * @param string $type
     * @param int $length
     *
     * @return ColumnSchemaBuilder
     */
    public function getColumnSchemaBuilder($type, $length = null)
    {
        return new ColumnSchemaBuilder($type, $length, $this->getConnection());
    }
}
