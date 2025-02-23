<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Data;

use Yiisoft\Db\Schema\Data\AbstractLazyArray;

final class LazyArray extends AbstractLazyArray
{
    protected function parse(string $value): array|null
    {
        return (new ArrayParser())->parse($value);
    }
}
