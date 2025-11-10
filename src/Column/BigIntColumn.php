<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Schema\Column\BigIntColumn as BaseBigIntColumn;

final class BigIntColumn extends BaseBigIntColumn implements SequenceColumnInterface
{
    use SequenceColumnTrait;
}
