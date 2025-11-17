<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Expression\Builder;

use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Pgsql\Expression\DateRangeValue;
use Yiisoft\Db\Pgsql\Expression\Int4RangeValue;
use Yiisoft\Db\Pgsql\Expression\Int8RangeValue;
use Yiisoft\Db\Pgsql\Expression\NumRangeValue;
use Yiisoft\Db\Pgsql\Expression\TsRangeValue;
use Yiisoft\Db\Pgsql\Expression\TsTzRangeValue;
use Yiisoft\Db\Schema\Column\ColumnInterface;

/**
 * @template T as DateRangeValue|Int4RangeValue|Int8RangeValue|NumRangeValue|TsRangeValue|TsTzRangeValue
 * @implements ExpressionBuilderInterface<T>
 */
abstract class AbstractRangeValueBuilder implements ExpressionBuilderInterface
{
    final public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $column = $this->getBoundColumn();
        return '\''
            . ($expression->includeLower ? '[' : '(')
            . $this->prepareBoundValue($expression->lower, $column)
            . ','
            . $this->prepareBoundValue($expression->upper, $column)
            . ($expression->includeUpper ? ']' : ')')
            . '\'';
    }

    abstract protected function getBoundColumn(): ColumnInterface;

    /**
     * @throws NotSupportedException
     */
    private function prepareBoundValue(mixed $value, ColumnInterface $boundColumn): string
    {
        $value = $boundColumn->dbTypecast($value);
        if ($value === null) {
            return '';
        }

        return (string) $value;
    }
}
