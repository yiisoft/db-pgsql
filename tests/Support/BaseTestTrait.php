<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Support;

trait BaseTestTrait
{
    protected function replaceQuotes(string $sql): string
    {
        return str_replace(['\\[', '\\]'], ['[', ']'], preg_replace('/(\[\[)|((?<!(\[))]])/', '"', $sql));
    }
}
