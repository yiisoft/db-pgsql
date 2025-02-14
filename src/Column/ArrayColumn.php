<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Pgsql\ArrayParser;
use Yiisoft\Db\Schema\Column\ArrayColumn as BaseArrayColumn;
use Yiisoft\Db\Syntax\ParserToArrayInterface;

final class ArrayColumn extends BaseArrayColumn
{
    protected function getParser(): ParserToArrayInterface
    {
        return new ArrayParser();
    }
}
