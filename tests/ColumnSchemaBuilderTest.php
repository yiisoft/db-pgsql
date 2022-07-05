<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Yiisoft\Db\TestSupport\TestColumnSchemaBuilderTrait;

/**
 * @group pgsql
 */
final class ColumnSchemaBuilderTest extends TestCase
{
    use TestColumnSchemaBuilderTrait;

    /**
     * @dataProvider typesProviderTrait
     *
     * @param string $expected
     * @param string $type
     * @param int|null $length
     * @param mixed $calls
     */
    public function testCustomTypes(string $expected, string $type, ?int $length, $calls): void
    {
        $this->checkBuildString($expected, $type, $length, $calls);
    }
}
