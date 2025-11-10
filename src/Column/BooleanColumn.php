<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Schema\Column\BooleanColumn as BaseBooleanColumn;

final class BooleanColumn extends BaseBooleanColumn
{
    public function phpTypecast(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        return $value && $value !== 'f';
    }
}
