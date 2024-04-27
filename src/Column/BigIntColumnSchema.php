<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Schema\Column\BigIntColumnSchema as BaseBigIntColumnSchema;

final class BigIntColumnSchema extends BaseBigIntColumnSchema implements SequenceColumnSchemaInterface
{
    use SequenceColumnSchemaTrait;
}
