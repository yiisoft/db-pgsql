<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Schema\Column\IntegerColumn as BaseIntegerColumn;

final class IntegerColumn extends BaseIntegerColumn implements SequenceColumnInterface
{
    use SequenceColumnTrait;
}
