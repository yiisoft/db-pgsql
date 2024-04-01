<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Builder;

use Yiisoft\Db\Expression\AbstractExpressionBuilder;
use Yiisoft\Db\Pgsql\SqlParser;

final class ExpressionBuilder extends AbstractExpressionBuilder
{
    protected function createSqlParser(string $sql): SqlParser
    {
        return new SqlParser($sql);
    }
}
