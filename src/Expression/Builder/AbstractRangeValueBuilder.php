<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Expression\Builder;

use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\Column\ColumnInterface;

/**
 * @template T as ExpressionInterface
 * @implements ExpressionBuilderInterface<T>
 */
abstract class AbstractRangeValueBuilder implements ExpressionBuilderInterface
{
    abstract protected function getBoundColumn(): ColumnInterface;

    /**
     * @throws NotSupportedException
     */
    final protected function buildRange(
        mixed $lower,
        mixed $upper,
        bool $includeLower,
        bool $includeUpper,
    ): string {
        $column = $this->getBoundColumn();
        return '\''
            . ($includeLower ? '[' : '(')
            . $this->prepareBoundValue($lower, $column)
            . ','
            . $this->prepareBoundValue($upper, $column)
            . ($includeUpper ? ']' : ')')
            . '\'';
    }

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
