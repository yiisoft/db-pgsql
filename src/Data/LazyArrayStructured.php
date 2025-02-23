<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Data;

use Yiisoft\Db\Schema\Data\AbstractLazyArrayStructured;

final class LazyArrayStructured extends AbstractLazyArrayStructured
{
    protected function parse(string $value): array|null
    {
        return (new StructuredParser())->parse($value);
    }
}
