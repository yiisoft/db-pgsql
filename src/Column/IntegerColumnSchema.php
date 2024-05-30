<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Schema\Column\IntegerColumnSchema as BaseIntegerColumnSchema;

final class IntegerColumnSchema extends BaseIntegerColumnSchema implements SequenceColumnSchemaInterface
{
    use SequenceColumnSchemaTrait;
}
