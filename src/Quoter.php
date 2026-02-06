<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use function str_contains;
use function strpos;
use function strrpos;
use function substr;

final class Quoter extends \Yiisoft\Db\Schema\Quoter
{
    public function quoteColumnName(string $name): string
    {
        if (str_contains($name, '(') || str_contains($name, '[[')) {
            return $name;
        }

        $dotPos = strrpos($name, '.');
        if ($dotPos !== false) {
            $prefix = $this->quoteTableName(substr($name, 0, $dotPos)) . '.';
            $name = substr($name, $dotPos + 1);
        } else {
            $prefix = '';
        }

        if (str_contains($name, '{{')) {
            return $name;
        }

        $bracketPos = strpos($name, '[');
        if ($bracketPos !== false) {
            $suffix = substr($name, $bracketPos);
            $name = substr($name, 0, $bracketPos);
        } else {
            $suffix = '';
        }

        return $prefix . $this->quoteSimpleColumnName($name) . $suffix;
    }
}
